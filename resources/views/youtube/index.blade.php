
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Video Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4 text-center">YouTube Keyword Analysis Tool</h1>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('youtube.search') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="keyword" class="form-control" placeholder="Enter keyword (e.g., 정치, 생활정보)" value="{{ $keyword ?? '' }}" required>
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                    @error('keyword')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </form>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @isset($videos)
            <h2 class="mb-3">Results for "<span class="fw-bold">{{ $keyword }}</span>"</h2>
            @if(count($videos) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Thumbnail</th>
                                <th>Type</th>
                                <th><a href="{{ route('youtube.search', array_merge(request()->query(), ['keyword' => $keyword, 'sort_by' => 'viewCount', 'sort_order' => ($sortBy == 'viewCount' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}">View Count @if($sortBy == 'viewCount') @if($sortOrder == 'asc') &triangle; @else &triangledown; @endif @endif</a></th>
                                <th><a href="{{ route('youtube.search', array_merge(request()->query(), ['keyword' => $keyword, 'sort_by' => 'subscriberCount', 'sort_order' => ($sortBy == 'subscriberCount' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}">Subscriber Count @if($sortBy == 'subscriberCount') @if($sortOrder == 'asc') &triangle; @else &triangledown; @endif @endif</a></th>
                                <th><a href="{{ route('youtube.search', array_merge(request()->query(), ['keyword' => $keyword, 'sort_by' => 'score', 'sort_order' => ($sortBy == 'score' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}">Score @if($sortBy == 'score') @if($sortOrder == 'asc') &triangle; @else &triangledown; @endif @endif</a></th>
                                <th>Channel</th>
                                <th><a href="{{ route('youtube.search', array_merge(request()->query(), ['keyword' => $keyword, 'sort_by' => 'publishedAt', 'sort_order' => ($sortBy == 'publishedAt' && $sortOrder == 'asc') ? 'desc' : 'asc'])) }}">Published Date @if($sortBy == 'publishedAt') @if($sortOrder == 'asc') &triangle; @else &triangledown; @endif @endif</a></th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($videos as $video)
                                <tr>
                                    <td>
                                        <img src="{{ $video['thumbnail'] }}" alt="thumbnail" width="120">
                                        {{ $video['title'] }}
                                    </td>
                                    <td>{{ stripos($video['title'], '#shorts') !== false ? 'Shorts' : 'Video' }}</td>
                                    <td>{{ number_format($video['viewCount']) }}</td>
                                    <td>{{ number_format($video['subscriberCount']) }}</td>
                                    <td>{{ number_format($video['score'], 2) }}</td>
                                    <td><a href="https://www.youtube.com/channel/{{ $video['channelId'] }}" target="_blank">{{ $video['channelTitle'] }}</a></td>
                                    <td>{{ $video['publishedAt'] }}</td>
                                    <td><a href="{{ $video['link'] }}" target="_blank" class="btn btn-sm btn-danger">Watch</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $videos->links() }}
                    </div>
                </div>
            @else
                <div class="alert alert-warning">No videos found for this keyword.</div>
            @endif
        @endisset
    </div>
</body>
</html>
