{{-- Corporate Retreat Package Toggle Field --}}
@props(['disabled' => false, 'checked' => false])

<div class="ops-field">
    <label for="property_is_corporate_retreat" style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0;">
        <input 
            type="checkbox" 
            id="property_is_corporate_retreat" 
            name="is_corporate_retreat" 
            value="1" 
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            style="width:18px; height:18px; cursor:pointer;">
        <span style="font-size:0.9rem; font-weight:600; color:#16212e;">
            Mark as Corporate Retreat Package
        </span>
    </label>
    <p style="font-size:0.8rem; color:#5a6a7a; margin:6px 0 0 26px;">
        This package will be displayed in the dedicated Corporate Retreats category with special branding and features.
    </p>
</div>
