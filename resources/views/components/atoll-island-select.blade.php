{{-- Shared Blade component for cascading Atoll → Island select dropdowns --}}
{{-- Usage: @include('components.atoll-island-select', ['selectedAtoll' => $selectedAtoll, 'selectedIsland' => $selectedIsland]) --}}

@php
    $selectedAtoll = $selectedAtoll ?? null;
    $selectedIsland = $selectedIsland ?? null;
    $fieldNameAtoll = $fieldNameAtoll ?? 'atoll_id';
    $fieldNameIsland = $fieldNameIsland ?? 'island_id';
    $labelAtoll = $labelAtoll ?? 'Atoll';
    $labelIsland = $labelIsland ?? 'Island';
    $requiredAtoll = $requiredAtoll ?? true;
    $requiredIsland = $requiredIsland ?? true;
    $cssClass = $cssClass ?? 'profile-input';
@endphp

<div class="atoll-island-select-group" style="display: flex; flex-direction: column; gap: 12px;">
    <div class="field-group">
        <label for="atoll-select">{{ $labelAtoll }}@if($requiredAtoll) <span style="color: #e41e3f;">*</span>@endif</label>
        <select
            id="atoll-select"
            name="{{ $fieldNameAtoll }}"
            class="{{ $cssClass }}"
            @if($requiredAtoll) required @endif
            data-role="atoll-selector"
        >
            <option value="">-- Select {{ strtolower($labelAtoll) }} --</option>
        </select>
    </div>

    <div class="field-group">
        <label for="island-select">{{ $labelIsland }}@if($requiredIsland) <span style="color: #e41e3f;">*</span>@endif</label>
        <select
            id="island-select"
            name="{{ $fieldNameIsland }}"
            class="{{ $cssClass }}"
            @if($requiredIsland) required @endif
            data-role="island-selector"
            disabled
        >
            <option value="">-- First select an {{ strtolower($labelAtoll) }} --</option>
        </select>
    </div>
</div>

<script>
(function() {
    const atollSelector = document.getElementById('atoll-select');
    const islandSelector = document.getElementById('island-select');
    const selectedAtoll = {{ json_encode($selectedAtoll) }};
    const selectedIsland = {{ json_encode($selectedIsland) }};

    // Fetch and populate atolls on page load
    async function loadAtolls() {
        try {
            const response = await fetch('/api/atoll-island/atolls');
            if (!response.ok) throw new Error('Failed to load atolls');
            const atolls = await response.json();
            
            atolls.forEach(atoll => {
                const option = document.createElement('option');
                option.value = atoll.id;
                option.textContent = atoll.name;
                if (selectedAtoll && selectedAtoll == atoll.id) {
                    option.selected = true;
                }
                atollSelector.appendChild(option);
            });

            // If an atoll was pre-selected, load its islands
            if (selectedAtoll) {
                await loadIslands(selectedAtoll);
            }
        } catch (error) {
            console.error('Error loading atolls:', error);
        }
    }

    // Fetch islands for selected atoll
    async function loadIslands(atollId) {
        islandSelector.innerHTML = '';
        if (!atollId) {
            islandSelector.disabled = true;
            islandSelector.innerHTML = '<option value="">-- First select an atoll --</option>';
            return;
        }

        islandSelector.disabled = false;

        const option = document.createElement('option');
        option.value = '';
        option.textContent = '-- Select island --';
        islandSelector.appendChild(option);

        try {
            const response = await fetch(`/api/atoll-island/atolls/${atollId}/islands`);
            if (!response.ok) throw new Error('Failed to load islands');
            const islands = await response.json();
            
            islands.forEach(island => {
                const opt = document.createElement('option');
                opt.value = island.id;
                opt.textContent = island.name;
                if (selectedIsland && selectedIsland == island.id) {
                    opt.selected = true;
                }
                islandSelector.appendChild(opt);
            });
        } catch (error) {
            console.error('Error loading islands:', error);
        }
    }

    // Handle atoll change
    atollSelector.addEventListener('change', function() {
        loadIslands(this.value);
    });

    // Load atolls on page load
    loadAtolls();
})();
</script>
