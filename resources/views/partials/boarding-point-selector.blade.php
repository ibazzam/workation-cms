{{-- Boarding Point Selector Modal for Liveaboard --}}
<div id="boardingPointModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); overflow: auto;">
    <div class="modal-content" style="background-color: #fff; margin: 5% auto; padding: 2rem; border-radius: 8px; max-width: 700px; width: 90%;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">Select Boarding & Disembarking Points</h3>
            <button type="button" class="close-modal" onclick="closeBoardingPointModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
        </div>

        <div class="journey-route-info" style="padding: 1rem; background: #e3f2fd; border-radius: 4px; margin-bottom: 1.5rem;">
            <p style="margin: 0; font-size: 0.9rem;">
                <strong>Journey:</strong> <span id="journeyRoute"></span> 
                <span style="display: block; color: #666; margin-top: 0.25rem;">Select where you board and where you disembark</span>
            </p>
        </div>

        <div class="boarding-selection" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="boarding-column">
                <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1976D2;">Boarding Point</label>
                <select id="boardingPoint" class="form-select" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;">
                    <option value="">-- Select boarding point --</option>
                </select>
            </div>
            <div class="disembarking-column">
                <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #C62828;">Disembarking Point</label>
                <select id="disembarkPoint" class="form-select" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;">
                    <option value="">-- Select disembarking point --</option>
                </select>
            </div>
        </div>

        <div class="pricing-info" style="padding: 1rem; background: #f5f5f5; border-radius: 4px; margin-top: 1.5rem; margin-bottom: 1.5rem;">
            <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">
                <strong>Estimated Package Price:</strong> 
                <span id="estimatedPrice" style="color: #1976D2; font-weight: 600; font-size: 1.1rem;">--</span>
            </p>
            <p style="margin: 0; font-size: 0.85rem; color: #666;">Price shown for reference. Final price confirmed at checkout.</p>
        </div>

        <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button type="button" class="btn-secondary" onclick="closeBoardingPointModal()" style="padding: 0.75rem 1.5rem; background: #fff; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-size: 1rem;">Cancel</button>
            <button type="button" class="btn-primary" onclick="confirmBoardingPointSelection()" style="padding: 0.75rem 1.5rem; background: #4CAF50; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">Confirm Selection</button>
        </div>
    </div>
</div>

<style>
.form-select {
    font-family: inherit;
}
.form-select option {
    padding: 0.5rem;
}
</style>

<script>
let pricingMatrix = {};
let stopoversList = [];
let journeyStartPoint = '';
let journeyEndPoint = '';

function initializeBoardingPointSelector(stopovers, pricing, startPoint, endPoint) {
    stopoversList = Array.isArray(stopovers) ? stopovers : [];
    pricingMatrix = pricing || {};
    journeyStartPoint = startPoint || '';
    journeyEndPoint = endPoint || '';
    
    document.getElementById('journeyRoute').textContent = `${journeyStartPoint} → ${journeyEndPoint}`;
    
    const boardingSelect = document.getElementById('boardingPoint');
    const disembarkSelect = document.getElementById('disembarkPoint');
    
    boardingSelect.innerHTML = '<option value="">-- Select boarding point --</option>';
    disembarkSelect.innerHTML = '<option value="">-- Select disembarking point --</option>';
    
    // Add start point (always allow boarding)
    const startOption = document.createElement('option');
    startOption.value = journeyStartPoint;
    startOption.textContent = `${journeyStartPoint} (Start)`;
    boardingSelect.appendChild(startOption);
    
    // Add stopovers that allow boarding
    stopoversList.forEach(stopover => {
        if (stopover.allow_embark) {
            const option = document.createElement('option');
            option.value = stopover.name;
            option.textContent = stopover.name;
            boardingSelect.appendChild(option);
        }
    });
    
    // Add end point (if allows boarding)
    const endStopover = stopoversList.find(s => s.name === journeyEndPoint);
    if (!endStopover || endStopover.allow_embark) {
        const endOption = document.createElement('option');
        endOption.value = journeyEndPoint;
        endOption.textContent = `${journeyEndPoint} (End)`;
        boardingSelect.appendChild(endOption);
    }
    
    // Add disembarking points
    const disembarkingPoints = [journeyStartPoint, journeyEndPoint];
    stopoversList.forEach(stopover => {
        if (stopover.allow_disembark) {
            disembarkingPoints.push(stopover.name);
        }
    });
    
    disembarkingPoints.forEach(point => {
        if (point) {
            const option = document.createElement('option');
            option.value = point;
            option.textContent = point;
            disembarkSelect.appendChild(option);
        }
    });
    
    // Add event listeners for price updates
    boardingSelect.addEventListener('change', updateEstimatedPrice);
    disembarkSelect.addEventListener('change', updateEstimatedPrice);
}

function updateEstimatedPrice() {
    const boardingPoint = document.getElementById('boardingPoint').value;
    const disembarkPoint = document.getElementById('disembarkPoint').value;
    const priceDisplay = document.getElementById('estimatedPrice');
    
    if (!boardingPoint || !disembarkPoint) {
        priceDisplay.textContent = '--';
        return;
    }
    
    // Try to find price in matrix (format: "From→To" or "From => To")
    const routeKey1 = `${boardingPoint}→${disembarkPoint}`;
    const routeKey2 = `${boardingPoint} → ${disembarkPoint}`;
    const routeKey3 = `${boardingPoint}=>${disembarkPoint}`;
    const routeKey4 = `${boardingPoint} => ${disembarkPoint}`;
    
    const price = pricingMatrix[routeKey1] 
        || pricingMatrix[routeKey2] 
        || pricingMatrix[routeKey3] 
        || pricingMatrix[routeKey4]
        || null;
    
    if (price !== null && price !== undefined) {
        priceDisplay.textContent = `MVR ${Number(price).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
    } else {
        priceDisplay.textContent = 'Price not available';
    }
}

function openBoardingPointModal() {
    const modal = document.getElementById('boardingPointModal');
    if (modal) modal.style.display = 'block';
}

function closeBoardingPointModal() {
    const modal = document.getElementById('boardingPointModal');
    if (modal) modal.style.display = 'none';
}

function confirmBoardingPointSelection() {
    const boardingPoint = document.getElementById('boardingPoint').value;
    const disembarkPoint = document.getElementById('disembarkPoint').value;
    
    if (!boardingPoint) {
        alert('Please select a boarding point');
        return;
    }
    if (!disembarkPoint) {
        alert('Please select a disembarking point');
        return;
    }
    
    if (boardingPoint === disembarkPoint) {
        alert('Boarding and disembarking points must be different');
        return;
    }
    
    // Store in hidden inputs
    const boardingInput = document.getElementById('boarding_point');
    const disembarkInput = document.getElementById('disembark_point');
    
    if (boardingInput) boardingInput.value = boardingPoint;
    if (disembarkInput) disembarkInput.value = disembarkPoint;
    
    // Update display
    const displayElement = document.getElementById('selectedBoardingPointsDisplay');
    if (displayElement) {
        displayElement.textContent = `${boardingPoint} → ${disembarkPoint}`;
    }
    
    closeBoardingPointModal();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('boardingPointModal');
    if (modal && event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>
