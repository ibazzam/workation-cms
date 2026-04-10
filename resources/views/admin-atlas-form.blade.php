<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'edit' ? 'Edit' : 'Create' }} {{ ucfirst($entity) }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #13212f; }
        .wrap { width: min(860px, calc(100% - 24px)); margin: 24px auto 40px; }
        .card { background: #fff; border: 1px solid #dbe3ed; border-radius: 12px; padding: 16px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .field input, .field select, .field textarea { border: 1px solid #cfd8e3; border-radius: 8px; padding: 10px; font: inherit; }
        .field textarea { min-height: 130px; resize: vertical; }
        .actions { display: flex; gap: 10px; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 8px; border: 1px solid #cfd8e3; text-decoration: none; color: #102033; background: #fff; font-weight: 600; }
        .btn.primary { background: #0f7f4f; color: #fff; border-color: #0f7f4f; }
        .errors { margin-bottom: 10px; padding: 10px 12px; border-radius: 8px; background: #fff2f2; border: 1px solid #f5c2c2; color: #842029; }
        .thumb { width: 180px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #dbe3ed; background: #e4edf6; }
        @media (max-width: 780px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px;">
        <h1 style="margin:0;">{{ $mode === 'edit' ? 'Edit' : 'Create' }} {{ ucfirst($entity) }}</h1>
        <a class="btn" href="/portal/admin/atlas">Back to Atlas</a>
    </div>

    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif

    <form class="card" method="POST" enctype="multipart/form-data" action="{{ $entity === 'atoll'
        ? ($mode === 'edit' ? '/portal/admin/atlas/atolls/' . (int) $record->id : '/portal/admin/atlas/atolls')
        : ($mode === 'edit' ? '/portal/admin/atlas/islands/' . (int) $record->id : '/portal/admin/atlas/islands') }}">
        @csrf

        <div class="row">
            <div class="field">
                <label>Name</label>
                <input name="name" type="text" maxlength="{{ $entity === 'atoll' ? '120' : '160' }}" value="{{ old('name', (string) ($record->name ?? '')) }}" required>
            </div>
            <div class="field">
                <label>Slug</label>
                <input name="slug" type="text" maxlength="{{ $entity === 'atoll' ? '140' : '180' }}" value="{{ old('slug', (string) ($record->slug ?? '')) }}" placeholder="Leave blank to auto-generate">
            </div>
        </div>

        @if ($entity === 'atoll')
            <div class="row">
                <div class="field">
                    <label>Code</label>
                    <input name="code" type="text" maxlength="20" value="{{ old('code', (string) ($record->code ?? '')) }}">
                </div>
                <div class="field">
                    <label>Wikipedia Title</label>
                    <input name="wikipedia_title" type="text" maxlength="220" value="{{ old('wikipedia_title', (string) ($record->wikipedia_title ?? '')) }}">
                </div>
            </div>
        @else
            <div class="row">
                <div class="field">
                    <label>Atoll</label>
                    <select name="atoll_id" required>
                        <option value="">Select atoll</option>
                        @foreach ($atolls as $atoll)
                            <option value="{{ (int) $atoll->id }}" @selected((string) old('atoll_id', (string) ($record->atoll_id ?? '')) === (string) $atoll->id)>
                                {{ $atoll->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Local Name</label>
                    <input name="local_name" type="text" maxlength="180" value="{{ old('local_name', (string) ($record->local_name ?? '')) }}">
                </div>
            </div>
            <div class="row">
                <div class="field">
                    <label>Island Type</label>
                    <select name="island_type" required>
                        @php $typeValue = old('island_type', (string) ($record->island_type ?? 'uninhabited')); @endphp
                        <option value="inhabited" @selected($typeValue === 'inhabited')>Inhabited</option>
                        <option value="uninhabited" @selected($typeValue === 'uninhabited')>Uninhabited</option>
                        <option value="resort" @selected($typeValue === 'resort')>Resort</option>
                    </select>
                </div>
                <div class="field">
                    <label>
                        <input type="checkbox" name="is_inhabited" value="1" @checked((bool) old('is_inhabited', (bool) ($record->is_inhabited ?? false)))>
                        Is inhabited
                    </label>
                    <label>Wikipedia Title</label>
                    <input name="wikipedia_title" type="text" maxlength="220" value="{{ old('wikipedia_title', (string) ($record->wikipedia_title ?? '')) }}">
                </div>
            </div>
        @endif

        <div class="field">
            <label>Description</label>
            <textarea name="description" maxlength="{{ $entity === 'atoll' ? '2000' : '3000' }}">{{ old('description', (string) ($record->description ?? '')) }}</textarea>
        </div>

        <div class="field">
            <label>Photo</label>
            <input name="photo" type="file" accept="image/*">
            @if($mode === 'edit' && !empty($record->photo_path))
                <div style="display:flex;gap:14px;align-items:center;margin-top:8px;">
                    <img class="thumb" src="{{ Storage::disk('public')->url($record->photo_path) }}" alt="Current photo">
                    <label><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
                </div>
            @endif
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">{{ $mode === 'edit' ? 'Save Changes' : 'Create' }}</button>
            <a class="btn" href="/portal/admin/atlas">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
