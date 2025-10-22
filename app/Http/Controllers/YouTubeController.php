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
        $videosList = [];

        try {
            $client = new Google_Client();
            $client->setDeveloperKey(env('YOUTUBE_API_KEY'));

            $youtube = new Google_Service_YouTube($client);

            // 1. Search for videos by keyword
            $searchResponse = $youtube->search->listSearch('id,snippet', [
                'q' => $keyword,
                'maxResults' => 100, // Adjust as needed
                'type' => 'video',
                'order' => 'date',
            ]);

            $videoIds = [];
            foreach ($searchResponse['items'] as $searchResult) {
                $videoIds[] = $searchResult['id']['videoId'];
            }

            if (!empty($videoIds)) {
                // 2. Get video details (statistics)
                $videoResponse = $youtube->videos->listVideos('snippet,statistics', [
                    'id' => implode(',', $videoIds),
                ]);

                $channelIds = [];
                $videoData = [];
                foreach ($videoResponse['items'] as $video) {
                    $channelIds[] = $video['snippet']['channelId'];
                    $videoData[$video['id']] = [
                        'title' => $video['snippet']['title'],
                        'thumbnail' => $video['snippet']['thumbnails']['default']['url'],
                        'publishedAt' => date('Y-m-d', strtotime($video['snippet']['publishedAt'])),
                        'viewCount' => $video['statistics']['viewCount'] ?? 0,
                        'link' => 'https://www.youtube.com/watch?v=' . $video['id'],
                        'channelId' => $video['snippet']['channelId'],
                    ];
                }

                // 3. Get channel details (subscriber count)
                $channelResponse = $youtube->channels->listChannels('statistics', [
                    'id' => implode(',', array_unique($channelIds)),
                ]);

                $channelSubscribers = [];
                foreach ($channelResponse['items'] as $channel) {
                    $channelSubscribers[$channel['id']] = $channel['statistics']['subscriberCount'] ?? 0;
                }

                // 4. Combine all data
                foreach ($videoData as $videoId => $data) {
                    $videosList[] = array_merge($data, [
                        'subscriberCount' => $channelSubscribers[$data['channelId']] ?? 0,
                    ]);
                }
            }

        } catch (\Google_Service_Exception $e) {
            return back()->with('error', 'API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }

        return view('youtube.index', [
            'videos' => $videosList,
            'keyword' => $keyword,
        ]);
    }
}
