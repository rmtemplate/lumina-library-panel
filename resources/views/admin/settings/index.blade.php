@extends('layouts.admin')
@section('title','Settings')
@section('page-title','System Settings')
@section('page-description','Konfigurasi global sistem seperti durasi pinjam, limit perpanjangan, dan nominal denda.')
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="lumina-card p-6">@csrf @method('PUT')
    <div class="grid gap-5 md:grid-cols-2">
        @foreach($settings as $key=>$setting)
            <div><label class="form-label">{{ str($setting->setting_key)->replace('_',' ')->title() }}</label><input name="settings[{{ $setting->setting_key }}]" value="{{ old('settings.'.$setting->setting_key,$setting->setting_value) }}" class="form-input"><p class="form-help">{{ $setting->description ?: $setting->setting_key }}</p></div>
        @endforeach
    </div>
    <div class="mt-6 flex justify-end"><button class="btn-primary" data-submit-label="Saving"><x-admin.icon name="check-circle" class="h-4 w-4" />Save Settings</button></div>
</form>
@endsection
