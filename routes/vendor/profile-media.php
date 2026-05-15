<?php

use App\Models\User;
use App\Support\ReservationPricingPolicy;
use App\Support\VendorPortalAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

Route::post('/portal/vendor/categories/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || normalizePortalRoleValue((string) $vendorUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your vendor account. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'categories' => ['required', 'array', 'min:1'],
        'categories.*' => ['required', 'string', 'max:80'],
        'onboarding_step' => ['nullable', 'integer', 'min:1', 'max:4'],
        'request_action' => ['nullable', Rule::in(['subscribe', 'open', 'release'])],
        'request_note' => ['nullable', 'string', 'max:2000'],
        'supporting_documents' => ['nullable', 'array'],
        'supporting_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
    ]);

    $allowedCategoryKeys = array_keys(vendorPortalCategoryMap());
    $normalizedCategories = [];
    foreach ($validated['categories'] as $inputCategory) {
        $canonicalCategory = vendorPortalCanonicalCategory((string) $inputCategory);
        if ($canonicalCategory === null || !in_array($canonicalCategory, $allowedCategoryKeys, true)) {
            return back()->withErrors([
                'profile' => 'Unsupported vendor category provided. Please select from the listed categories.',
            ])->withInput();
        }

        $normalizedCategories[] = $canonicalCategory;
    }
    $normalizedCategories = array_values(array_unique($normalizedCategories));
    $requestAction = (string) ($validated['request_action'] ?? 'subscribe');
    $requiredDocuments = vendorPortalRequiredDocumentsForCategories($normalizedCategories);

    if (in_array($requestAction, ['subscribe', 'open'], true) && $requiredDocuments !== [] && empty($validated['supporting_documents'])) {
        return back()->withErrors([
            'profile' => 'Supporting documents are required for the selected categories: ' . implode('; ', $requiredDocuments),
        ])->withInput();
    }

    if (Schema::hasColumn('users', 'portal_service_categories')) {
        $vendorUser->portal_service_categories = json_encode($normalizedCategories);
    }

    if (Schema::hasColumn('users', 'vendor_onboarding_step')) {
        $vendorUser->vendor_onboarding_step = (int) ($validated['onboarding_step'] ?? 1);
    }

    $uploadedDocuments = [];
    foreach ((array) ($validated['supporting_documents'] ?? []) as $document) {
        if ($document instanceof \Illuminate\Http\UploadedFile) {
            $storedPath = $document->store('vendor/compliance-documents/' . (int) $vendorUser->id, 'public');
            if (is_string($storedPath) && $storedPath !== '') {
                $uploadedDocuments[] = [
                    'name' => (string) $document->getClientOriginalName(),
                    'path' => $storedPath,
                    'url' => Storage::disk('public')->url($storedPath),
                ];
            }
        }
    }

    if ($uploadedDocuments !== [] && Schema::hasColumn('users', 'vendor_verification_documents')) {
        $vendorUser->vendor_verification_documents = json_encode($uploadedDocuments);
    }

    $vendorUser->save();

    if (function_exists('portalActionRequestsEnabled') && function_exists('createPortalActionRequest') && portalActionRequestsEnabled()) {
        $requestReason = trim((string) ($validated['request_note'] ?? ''));
        createPortalActionRequest(
            'vendor.category_request',
            (int) $vendorUser->id,
            null,
            (string) ($vendorUser->username ?: $vendorUser->email),
            $requestReason !== '' ? $requestReason : 'Vendor category request submitted from portal.',
            [
                'request_action' => $requestAction,
                'categories' => $normalizedCategories,
                'documents' => $uploadedDocuments,
                'required_documents' => $requiredDocuments,
                'requested_via' => 'vendor_portal',
            ]
        );
    }

    VendorPortalAuditLogger::log('vendor_profile.categories_updated', [
        'severity' => 'info',
        'target_identifier' => 'vendor:' . (int) $vendorUser->id,
        'category_count' => count($normalizedCategories),
        'request_action' => $requestAction,
        'documents_uploaded' => count($uploadedDocuments),
    ]);

    return back()->with('portal_notice', 'Category request saved and sent for admin validation review.');
});

Route::post('/portal/vendor/profile/update', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $vendorUser = $vendorUserId > 0 ? User::query()->find($vendorUserId) : null;
    if (!$vendorUser instanceof User || normalizePortalRoleValue((string) $vendorUser->portal_role) !== 'VENDOR') {
        return back()->withErrors([
            'profile' => 'Unable to resolve your vendor account. Please sign in again.',
        ]);
    }

    $validated = $request->validate([
        'display_name' => ['required', 'string', 'max:120'],
        'contact_phone' => ['required', 'string', 'max:40'],
        'company_name' => ['required', 'string', 'max:190'],
        'business_registration_number' => ['required', 'string', 'max:120'],
        'business_license_number' => ['nullable', 'string', 'max:120'],
        'contact_person_name' => ['required', 'string', 'max:190'],
        'contact_person_phone' => ['required', 'string', 'max:60'],
        'contact_person_email' => ['required', 'email:rfc', 'max:190'],
        'contact_person_id_number' => ['required', 'string', 'max:120'],
    ]);

    $vendorUser->name = trim((string) $validated['display_name']);
    if (Schema::hasColumn('users', 'phone')) {
        $vendorUser->phone = vendorNormalizePhoneNumber((string) ($validated['contact_phone'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_company_name')) {
        $vendorUser->vendor_company_name = trim((string) ($validated['company_name'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_business_registration_number')) {
        $vendorUser->vendor_business_registration_number = trim((string) ($validated['business_registration_number'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_business_license_number')) {
        $vendorUser->vendor_business_license_number = trim((string) ($validated['business_license_number'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_name')) {
        $vendorUser->vendor_contact_person_name = trim((string) ($validated['contact_person_name'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_phone')) {
        $vendorUser->vendor_contact_person_phone = vendorNormalizePhoneNumber((string) ($validated['contact_person_phone'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_email')) {
        $vendorUser->vendor_contact_person_email = strtolower(trim((string) ($validated['contact_person_email'] ?? '')));
    }
    if (Schema::hasColumn('users', 'vendor_contact_person_id_number')) {
        $vendorUser->vendor_contact_person_id_number = trim((string) ($validated['contact_person_id_number'] ?? ''));
    }
    if (Schema::hasColumn('users', 'vendor_legal_documents_submitted_at')) {
        $vendorUser->vendor_legal_documents_submitted_at = now();
    }
    if (Schema::hasColumn('users', 'vendor_verification_status')) {
        $currentStatus = strtolower(trim((string) ($vendorUser->vendor_verification_status ?? 'pending')));
        if (in_array($currentStatus, ['pending', 'rejected'], true)) {
            $vendorUser->vendor_verification_status = 'under_review';
        }
    }
    $vendorUser->save();

    VendorPortalAuditLogger::log('vendor_profile.compliance_updated', [
        'severity' => 'info',
        'target_identifier' => 'vendor:' . (int) $vendorUser->id,
    ]);

    session([
        'portal_vendor_user' => $vendorUser->name,
    ]);

    return back()->with('portal_notice', 'Profile and compliance details saved. Verification review status updated.');
});

Route::post('/portal/vendor/media/upload', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $validated = $request->validate([
        'entity_type' => ['required', Rule::in(['property', 'service', 'room', 'profile', 'menu', 'vehicle'])],
        'entity_id' => ['nullable', 'integer', 'min:1'],
        'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'photos' => ['nullable', 'array', 'min:1'],
        'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'alt_text' => ['required', 'string', 'max:190'],
        'is_primary' => ['nullable', 'boolean'],
        'primary_upload_index' => ['nullable', 'integer', 'min:0'],
    ]);

    $entityType = (string) $validated['entity_type'];
    $entityId = filled($validated['entity_id'] ?? null) ? (int) $validated['entity_id'] : null;

    if (in_array($entityType, ['property', 'room'], true) && ($entityId === null || $entityId <= 0)) {
        return back()->withErrors(['profile' => 'Choose a valid property or room before uploading photos.'])->withInput();
    }

    if ($entityType === 'property') {
        $propertyExists = \App\Support\VendorPropertyCompatibilityReader::vendorOwnsProperty((int) $entityId, $vendorUserId);
        if (!$propertyExists) {
            return back()->withErrors(['profile' => 'Property not found for this vendor account.'])->withInput();
        }
    }

    if ($entityType === 'room') {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return back()->withErrors(['profile' => 'Room categories table is not ready. Run migrations first.']);
        }

        $roomExists = DB::table('vendor_property_room_categories')
            ->where('id', (int) $entityId)
            ->where('vendor_user_id', $vendorUserId)
            ->exists();

        if (!$roomExists) {
            return back()->withErrors(['profile' => 'Room not found for this vendor account.'])->withInput();
        }
    }

    $uploadedFiles = [];
    if ($request->hasFile('photos')) {
        $candidateFiles = $request->file('photos');
        if (is_array($candidateFiles)) {
            $uploadedFiles = array_values(array_filter($candidateFiles));
        }
    }
    if ($uploadedFiles === [] && $request->hasFile('photo')) {
        $singleFile = $request->file('photo');
        if ($singleFile) {
            $uploadedFiles[] = $singleFile;
        }
    }

    if ($uploadedFiles === []) {
        return back()->withErrors(['profile' => 'Please choose at least one photo to upload.'])->withInput();
    }

    $selectedPrimaryIndex = isset($validated['primary_upload_index'])
        ? (int) $validated['primary_upload_index']
        : 0;
    $selectedPrimaryIndex = max(0, min(count($uploadedFiles) - 1, $selectedPrimaryIndex));

    // Batch upload defines one clear primary image for this entity.
    DB::table('vendor_listing_media')
        ->where('vendor_user_id', $vendorUserId)
        ->where('entity_type', $entityType)
        ->where('entity_id', $entityId)
        ->update(['is_primary' => false, 'updated_at' => now()]);

    $format = vendorPortalPreferredMediaOutputFormat();
    $outputExtension = (string) ($format['extension'] ?? 'jpg');
    $outputMime = (string) ($format['mime'] ?? 'image/jpeg');

    foreach ($uploadedFiles as $fileIndex => $file) {
        $imageSize = @getimagesize($file->getPathname());
        if (!is_array($imageSize) || count($imageSize) < 2) {
            return back()->withErrors(['profile' => 'One of the uploaded files is not a valid image.'])->withInput();
        }

        $widthPx = (int) $imageSize[0];
        $heightPx = (int) $imageSize[1];
        $fileSizeKb = (int) ceil(((int) $file->getSize()) / 1024);

        $sourceImage = vendorPortalCreateImageResourceFromFile(
            (string) $file->getPathname(),
            (string) ($file->getMimeType() ?? '')
        );
        if ($sourceImage === null) {
            return back()->withErrors(['profile' => 'Unable to process one of the uploaded images. Use JPG, PNG, or WebP.'])->withInput();
        }

        $storagePrefix = 'vendor-listings/' . $vendorUserId;
        $entityToken = $entityType . '-' . ($entityId ?? 'shared');
        $baseToken = now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '-' . $entityToken . '-' . $fileIndex;
        $bannerPath = $storagePrefix . '/' . $baseToken . '-banner.' . $outputExtension;
        $thumbPath = $storagePrefix . '/' . $baseToken . '-thumb.' . $outputExtension;

        $bannerImage = vendorPortalResizeImageToFill($sourceImage, $widthPx, $heightPx, 1600, 900);
        $thumbImage = vendorPortalResizeImageToFill($sourceImage, $widthPx, $heightPx, 480, 320);

        $bannerWritten = $bannerImage !== null
            ? vendorPortalWriteMediaVariant($bannerImage, $bannerPath, $outputExtension)
            : false;
        $thumbWritten = $thumbImage !== null
            ? vendorPortalWriteMediaVariant($thumbImage, $thumbPath, $outputExtension)
            : false;

        if (is_resource($sourceImage) || $sourceImage instanceof \GdImage) {
            imagedestroy($sourceImage);
        }
        if ((is_resource($bannerImage) || $bannerImage instanceof \GdImage)) {
            imagedestroy($bannerImage);
        }
        if ((is_resource($thumbImage) || $thumbImage instanceof \GdImage)) {
            imagedestroy($thumbImage);
        }

        if (!$bannerWritten || !$thumbWritten) {
            return back()->withErrors(['profile' => 'Failed to generate optimized variants for one of the images.'])->withInput();
        }

        $storedBannerSizeBytes = 0;
        try {
            $storedBannerSizeBytes = (int) (Storage::disk(vendorPortalMediaDiskName())->size($bannerPath) ?? 0);
        } catch (\Throwable $e) {
            $storedBannerSizeBytes = 0;
        }
        $storedBannerSizeKb = (int) ceil($storedBannerSizeBytes / 1024);
        $qualityGrade = $storedBannerSizeKb > 0 && $storedBannerSizeKb <= 900 ? 'A' : 'B';

        $mediaPayload = [
            'vendor_user_id' => $vendorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'file_path' => (string) $bannerPath,
            'mime_type' => $outputMime,
            'alt_text' => trim((string) ($validated['alt_text'] ?? '')),
            'is_primary' => $fileIndex === $selectedPrimaryIndex,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('vendor_listing_media', 'width_px')) {
            $mediaPayload['width_px'] = 1600;
        }
        if (Schema::hasColumn('vendor_listing_media', 'height_px')) {
            $mediaPayload['height_px'] = 900;
        }
        if (Schema::hasColumn('vendor_listing_media', 'file_size_kb')) {
            $mediaPayload['file_size_kb'] = $storedBannerSizeKb > 0 ? $storedBannerSizeKb : $fileSizeKb;
        }
        if (Schema::hasColumn('vendor_listing_media', 'quality_grade')) {
            $mediaPayload['quality_grade'] = $qualityGrade;
        }

        DB::table('vendor_listing_media')->insert($mediaPayload);
    }

    VendorPortalAuditLogger::log('vendor_media.uploaded', [
        'severity' => 'info',
        'target_identifier' => 'media:' . $entityType . ':' . ($entityId ?? 'shared'),
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'files_uploaded' => count($uploadedFiles),
    ]);

    return vendorPortalListingsBackResponse(
        'Photos uploaded successfully.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});

Route::post('/portal/vendor/media/bulk-delete', function (Request $request) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'media_ids' => ['required', 'array', 'min:1'],
        'media_ids.*' => ['required', 'integer', 'min:1'],
    ]);

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $mediaIds = array_values(array_unique(array_map(static fn ($id) => (int) $id, $validated['media_ids'] ?? [])));
    if ($mediaIds === []) {
        return back()->withErrors(['profile' => 'Select at least one photo to remove.']);
    }

    $mediaRecords = DB::table('vendor_listing_media')
        ->where('vendor_user_id', $vendorUserId)
        ->whereIn('id', $mediaIds)
        ->get();

    if ($mediaRecords->isEmpty()) {
        return back()->withErrors(['profile' => 'No selected media items were found for this vendor account.']);
    }

    foreach ($mediaRecords as $mediaRecord) {
        vendorPortalDeleteMediaRecord($mediaRecord, $vendorUserId);
    }

    VendorPortalAuditLogger::log('vendor_media.bulk_deleted', [
        'severity' => 'warn',
        'target_identifier' => 'media:bulk-delete',
        'deleted_count' => (int) $mediaRecords->count(),
    ]);

    return vendorPortalListingsBackResponse(
        count($mediaRecords) . ' photo(s) removed.',
        4,
        vendorPortalMediaPanelContextFromRequest($request)
    );
});

Route::post('/portal/vendor/media/{media}/primary', function (Request $request, int $media) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    if (!$mediaRecord) {
        return back()->withErrors(['profile' => 'Media item not found for this vendor account.']);
    }

    $entityType = (string) ($mediaRecord->entity_type ?? '');
    $entityId = isset($mediaRecord->entity_id) ? (int) $mediaRecord->entity_id : null;

    DB::table('vendor_listing_media')
        ->where('vendor_user_id', $vendorUserId)
        ->where('entity_type', $entityType)
        ->where('entity_id', $entityId)
        ->update([
            'is_primary' => false,
            'updated_at' => now(),
        ]);

    DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'is_primary' => true,
            'updated_at' => now(),
        ]);

    VendorPortalAuditLogger::log('vendor_media.primary_updated', [
        'severity' => 'info',
        'target_identifier' => 'media:' . $media,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
    ]);

    return vendorPortalListingsBackResponse(
        'Primary photo updated.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});

Route::post('/portal/vendor/media/{media}/update', function (Request $request, int $media) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $validated = $request->validate([
        'alt_text' => ['required', 'string', 'max:190'],
    ]);

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $updated = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->update([
            'alt_text' => trim((string) $validated['alt_text']),
            'updated_at' => now(),
        ]);

    if ($updated <= 0) {
        return back()->withErrors(['profile' => 'Media item not found for this vendor account.']);
    }

    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    $entityType = $mediaRecord ? (string) ($mediaRecord->entity_type ?? '') : null;
    $entityId = $mediaRecord && isset($mediaRecord->entity_id) ? (int) $mediaRecord->entity_id : null;

    VendorPortalAuditLogger::log('vendor_media.metadata_updated', [
        'severity' => 'info',
        'target_identifier' => 'media:' . $media,
        'entity_type' => (string) ($entityType ?? ''),
        'entity_id' => $entityId,
    ]);

    return vendorPortalListingsBackResponse(
        'Photo details updated.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});

Route::post('/portal/vendor/media/{media}/delete', function (Request $request, int $media) {
    if (!session('portal_vendor_authenticated', false)) {
        return redirect('/portal/vendor/login');
    }

    if (!Schema::hasTable('vendor_listing_media')) {
        return back()->withErrors(['profile' => 'Media storage table is not ready. Run migrations first.']);
    }

    $vendorUserId = (int) session('portal_vendor_user_id', 0);
    $mediaRecord = DB::table('vendor_listing_media')
        ->where('id', $media)
        ->where('vendor_user_id', $vendorUserId)
        ->first();

    if (!$mediaRecord) {
        return back()->withErrors(['profile' => 'Media item not found for this vendor account.']);
    }

    $entityType = (string) ($mediaRecord->entity_type ?? '');
    $entityId = isset($mediaRecord->entity_id) ? (int) $mediaRecord->entity_id : null;

    vendorPortalDeleteMediaRecord($mediaRecord, $vendorUserId);

    VendorPortalAuditLogger::log('vendor_media.deleted', [
        'severity' => 'warn',
        'target_identifier' => 'media:' . $media,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
    ]);

    return vendorPortalListingsBackResponse(
        'Photo removed.',
        4,
        vendorPortalMediaPanelContextFromRequest($request, $entityType, $entityId)
    );
});
