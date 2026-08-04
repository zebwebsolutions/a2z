@props(['data' => []])

@if(!empty($data))
    <script type="application/ld+json">{!! \App\Support\StructuredData::encode($data) !!}</script>
@endif
