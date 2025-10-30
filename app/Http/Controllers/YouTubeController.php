<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_YouTube;

class YouTubeController extends Controller
{
    public function index()
    {
        return view('youtube.index');
    }

    public function search(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
        ]);

        $keyword = $request->input('keyword');
        $videoType = $request->input('video_type', 'all');

        if ($videoType === 'shorts') {
            $keyword .= ' #shorts';
        }

        $videosList = [];
        $nextPageToken = null;

        try {
            $client = new Google_Client();
            $client->setDeveloperKey(env('YOUTUBE_API_KEY'));
            $youtube = new Google_Service_YouTube($client);

            // 1. Search for up to 1000 videos by keyword
            for ($i = 0; $i < 20; $i++) { // 20 * 50 = 1000
                $searchResponse = $youtube->search->listSearch('id,snippet', [
                    'q' => $keyword,
                    'maxResults' => 50,
                    'type' => 'video',
                    'order' => 'date',
                    'pageToken' => $nextPageToken,
                ]);

                $videoIds = [];
                foreach ($searchResponse['items'] as $searchResult) {
                    $videoIds[] = $searchResult['id']['videoId'];
                }

                if (!empty($videoIds)) {
                    $videoResponse = $youtube->videos->listVideos('snippet,statistics', [
                        'id' => implode(',', $videoIds),
                    ]);

                    $channelIds = [];
                    foreach ($videoResponse['items'] as $video) {
                        $channelIds[] = $video['snippet']['channelId'];
                        $videosList[] = [
                            'title' => $video['snippet']['title'],
                            'thumbnail' => $video['snippet']['thumbnails']['default']['url'],
                            'publishedAt' => date('Y-m-d', strtotime($video['snippet']['publishedAt'])),
                            'viewCount' => $video['statistics']['viewCount'] ?? 0,
                            'link' => 'https://www.youtube.com/watch?v=' . $video['id'],
                            'channelId' => $video['snippet']['channelId'],
                            'channelTitle' => $video['snippet']['channelTitle'],
                        ];
                    }

                    $channelResponse = $youtube->channels->listChannels('statistics', [
                        'id' => implode(',', array_unique($channelIds)),
                    ]);

                    $channelSubscribers = [];
                    foreach ($channelResponse['items'] as $channel) {
                        $channelSubscribers[$channel['id']] = $channel['statistics']['subscriberCount'] ?? 0;
                    }

                    foreach ($videosList as &$video) {
                        if (isset($channelSubscribers[$video['channelId']])) {
                            $video['subscriberCount'] = $channelSubscribers[$video['channelId']];
                        }
                    }
                }

                $nextPageToken = $searchResponse->getNextPageToken();
                if (!$nextPageToken) {
                    break;
                }
            }

            // Filter videos where view count is less than subscriber count
            $videosList = array_filter($videosList, function ($video) {
                return ($video['viewCount'] ?? 0) >= ($video['subscriberCount'] ?? 0);
            });

            if ($videoType === 'video') {
                $videosList = array_filter($videosList, function ($video) {
                    return stripos($video['title'], '#shorts') === false;
                });
            }

            // Calculate score and sort
            foreach ($videosList as &$video) {
                $subscriberCount = $video['subscriberCount'] ?? 0;
                $viewCount = $video['viewCount'] ?? 0;
                if ($subscriberCount > 0) {
                    $video['score'] = round(($viewCount / $subscriberCount) / 10, 2);
                } else {
                    $video['score'] = 0;
                }
            }

            usort($videosList, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            // Sort based on user input
            $sortBy = $request->input('sort_by', 'score');
            $sortOrder = $request->input('sort_order', 'desc');

            usort($videosList, function ($a, $b) use ($sortBy, $sortOrder) {
                if ($sortBy === 'publishedAt') {
                    $aValue = strtotime($a[$sortBy]);
                    $bValue = strtotime($b[$sortBy]);
                } else {
                    $aValue = $a[$sortBy];
                    $bValue = $b[$sortBy];
                }

                if ($sortOrder === 'asc') {
                    return $aValue <=> $bValue;
                }
                return $bValue <=> $aValue;
            });

        } catch (\Google_Service_Exception $e) {
            return back()->with('error', 'API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }

        // Paginate the results
        $page = $request->get('page', 1);
        $perPage = 100;
        $offset = ($page * $perPage) - $perPage;
        $paginatedVideos = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($videosList, $offset, $perPage),
            count($videosList),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $paginatedVideos->appends($request->all());

        return view('youtube.index', [
            'videos' => $paginatedVideos,
            'keyword' => $keyword,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'videoType' => $videoType,
        ]);
    }
}
