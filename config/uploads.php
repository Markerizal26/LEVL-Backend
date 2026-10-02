<?php

return [
    'max_file_size_kb' => (int) env('UPLOAD_MAX_FILE_SIZE_KB', 2048),
    'max_video_size_kb' => (int) env('UPLOAD_MAX_VIDEO_SIZE_KB', 51200),
];
