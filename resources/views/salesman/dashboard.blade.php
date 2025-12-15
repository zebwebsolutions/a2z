@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold">Salesman Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}! This section will soon show your repair and sales stats.</p>
</div>
@endsection
