{{-- Lesson section: prose paragraphs (+ bullets via LessonText::prose) --}}
<div class="text-[15px] leading-7 text-gray-800">
    {!! \App\Support\LessonText::prose($payload['text'] ?? '') !!}
</div>
