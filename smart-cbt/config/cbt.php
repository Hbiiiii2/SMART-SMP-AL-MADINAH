<?php

return [
    'essay_max_photos' => (int) env('CBT_ESSAY_MAX_PHOTOS', 3),
    'essay_photo_max_kb' => (int) env('CBT_ESSAY_PHOTO_MAX_KB', 5120),
    'essay_photo_max_dimension' => (int) env('CBT_ESSAY_PHOTO_MAX_DIM', 4096),
    'essay_photo_disk' => env('CBT_ESSAY_PHOTO_DISK', 'exam_private'),
];
