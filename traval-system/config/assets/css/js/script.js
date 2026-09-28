<script>
var bookingState = {
    packageId: 0,
    packagePrice: 0,
    discountAmount: 0,
    isProcessing: false,
    isModalOpen: false
};

function showToast(message, type) {
    type = type || 'success';
    var existing = document.querySelector('.custom-toast');
    if (existing) existing.remove();
    
    var toast = document.createElement('div');
    toast.className = 'custom-toast';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 10px;
        z-index: 9999999;
        font-weight: 600;
        font-size: 14px;
        animation: slideIn 0.4s ease;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        background: ${type === 'error' ? '#e74c3c' : '#27ae60'};
        color: white;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.style.animation = 'slideOut 0.4s ease forwards';
        setTimeout(function() { toast.remove(); }, 500);
    }, 4000);
}

// ============================================
// OPEN BOOKING MODAL
// ============================================
function openBookingModal(packageId, buttonElement) {
    console.log('Opening booking modal for package:', packageId);
    
    if (bookingState.isProcessing) return;
    
    if (bookingState.isModalOpen) {
        closeBookingModal();
        setTimeout(function() {
            openBookingModal(packageId, buttonElement);
        }, 400);
        return;
    }
    
    bookingState.isProcessing = true;
    bookingState.packageId = packageId;
    
    var spinner = document.getElementById('globalSpinner');
    if (spinner) spinner.classList.add('show');
    
    var btn = buttonElement || document.querySelector('.book-btn[data-id="' + packageId + '"]');
    if (btn) {
        var originalHtml = btn.innerHTML;
        btn.setAttribute('data-original-html', originalHtml);
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        btn.disabled = true;
    }
    
    document.getElementById('bookingPackageId').value = packageId;
    document.getElementById('selectedPackageName').textContent = 'Loading package details...';
    
    var url = 'get_package_details.php?id=' + packageId + '&t=' + Date.now();
    
    fetch(url)
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Package data received:', data);
            bookingState.isProcessing = false;
            
            if (btn) {
                btn.innerHTML = btn.getAttribute('data-original-html') || '<i class="fas fa-calendar-check"></i> Book Now';
                btn.disabled = false;
            }
            
            if (spinner) spinner.classList.remove('show');
            
            if (data.success) {
                bookingState.packagePrice = parseFloat(data.price) || 0;
                
                var displayName = data.name + (data.destination ? ' - ' + data.destination : '');
                document.getElementById('selectedPackageName').textContent = displayName;
                
                var today = new Date().toISOString().split('T')[0];
                document.getElementById('bookingStartDate').value = today;
                document.getElementById('bookingStartDate').min = today;
                
                document.getElementById('bookingAdults').value = 1;
                document.getElementById('bookingChildren').value = 0;
                document.getElementById('promoCodeInput').value = '';
                document.getElementById('promoMessage').innerHTML = '';
                document.getElementById('discountDisplay').textContent = '';
                bookingState.discountAmount = 0;
                
                calculateTotal();
                
                var modal = document.getElementById('bookingModal');
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                
                setTimeout(function() {
                    modal.classList.add('show');
                }, 50);
                
                document.body.style.overflow = 'hidden';
                bookingState.isModalOpen = true;
                
                setTimeout(function() {
                    document.getElementById('bookingStartDate').focus();
                }, 500);
                
            } else {
                showToast(data.message || 'Error loading package details.', 'error');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            bookingState.isProcessing = false;
            
            if (btn) {
                btn.innerHTML = btn.getAttribute('data-original-html') || '<i class="fas fa-calendar-check"></i> Book Now';
                btn.disabled = false;
            }
            
            if (spinner) spinner.classList.remove('show');
            
            showToast('Error loading package. Please try again.', 'error');
        });
}

// ============================================
// CLOSE BOOKING MODAL
// ============================================
function closeBookingModal() {
    var modal = document.getElementById('bookingModal');
    modal.classList.remove('show');
    
    setTimeout(function() {
        modal.style.display = 'none';
        modal.style.alignItems = '';
        modal.style.justifyContent = '';
    }, 300);
    
    document.body.style.overflow = '';
    bookingState.isModalOpen = false;
    bookingState.isProcessing = false;
}

// ============================================
// CALCULATE TOTAL
// ============================================
function calculateTotal() {
    var adults = parseInt(document.getElementById('bookingAdults').value) || 0;
    var children = parseInt(document.getElementById('bookingChildren').value) || 0;
    var insurance = parseFloat(document.getElementById('insurancePrice').value) || 0;
    var packagePrice = bookingState.packagePrice || 0;
    
    if (adults < 1) {
        document.getElementById('bookingAdults').value = 1;
        adults = 1;
    }
    
    var total = (packagePrice * adults) + ((packagePrice / 2) * children);
    total = total + insurance - bookingState.discountAmount;
    if (total < 0) total = 0;
    
    document.getElementById('totalAmountDisplay').textContent = 'LKR ' + total.toFixed(2);
    
    if (bookingState.discountAmount > 0) {
        document.getElementById('discountDisplay').textContent = 'Discount: LKR ' + bookingState.discountAmount.toFixed(2);
    } else {
        document.getElementById('discountDisplay').textContent = '';
    }
    
    return total;
}

// ============================================
// APPLY PROMO CODE
// ============================================
function applyPromoCode() {
    var code = document.getElementById('promoCodeInput').value.trim();
    var msg = document.getElementById('promoMessage');
    
    if (!code) {
        msg.innerHTML = '<span style="color:#e74c3c;">Please enter a promo code.</span>';
        return;
    }
    
    msg.innerHTML = '<span style="color:#f39c12;"><i class="fas fa-spinner fa-spin"></i> Validating...</span>';
    
    var adults = parseInt(document.getElementById('bookingAdults').value) || 0;
    var children = parseInt(document.getElementById('bookingChildren').value) || 0;
    var subtotal = (bookingState.packagePrice * adults) + ((bookingState.packagePrice / 2) * children);
    var insurance = parseFloat(document.getElementById('insurancePrice').value) || 0;
    subtotal += insurance;
    
    fetch('validate_promo.php?code=' + encodeURIComponent(code) + '&subtotal=' + subtotal)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                bookingState.discountAmount = parseFloat(data.discount) || 0;
                msg.innerHTML = '<span style="color:#27ae60;">✅ Promo applied! Discount: LKR ' + data.discount.toFixed(2) + '</span>';
                calculateTotal();
            } else {
                msg.innerHTML = '<span style="color:#e74c3c;">❌ ' + data.message + '</span>';
            }
        })
        .catch(function(error) {
            msg.innerHTML = '<span style="color:#e74c3c;">❌ Error applying promo code.</span>';
        });
}

// ============================================
// UPDATE INSURANCE PRICE
// ============================================
function updateInsurancePrice() {
    var type = document.getElementById('insuranceType').value;
    var prices = { 'None': 0, 'Standard': 1500, 'Premium': 3500, 'Comprehensive': 5500 };
    document.getElementById('insurancePrice').value = prices[type] || 0;
    calculateTotal();
}

// ============================================
// TOGGLE VEHICLE FIELDS
// ============================================
function toggleVehicleFields() {
    var vehicleType = document.getElementById('vehicleType').value;
    var fields = document.getElementById('vehicleFields');
    fields.style.display = vehicleType ? 'grid' : 'none';
}

// ============================================
// VALIDATE BOOKING FORM - FIXED
// ============================================
function validateBookingForm() {
    console.log('Validating booking form...');
    
    var startDate = document.getElementById('bookingStartDate');
    var adults = document.getElementById('bookingAdults');
    var packageId = document.getElementById('bookingPackageId');
    var btn = document.getElementById('confirmBookingBtn');
    
    // Check if start date is selected
    if (!startDate.value) {
        showToast('Please select a start date.', 'error');
        startDate.focus();
        return false;
    }
    
    // Check if adults is valid
    if (parseInt(adults.value) < 1) {
        showToast('Please select at least 1 adult.', 'error');
        adults.focus();
        return false;
    }
    
    // Check if package ID is set
    if (!packageId.value || parseInt(packageId.value) <= 0) {
        showToast('Invalid package selected. Please try again.', 'error');
        return false;
    }
    
    // Check if we're already processing
    if (bookingState.isProcessing) {
        return false;
    }
    
    // Set processing state
    bookingState.isProcessing = true;
    
    // Update button
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
    }
    
    console.log('Form validation passed, submitting...');
    return true;
}

// ============================================
// OPEN DETAILS MODAL
// ============================================
function openDetailsModal(packageData) {
    var body = document.getElementById('detailsModalBody');
    var price = packageData.price || 0;
    
    body.innerHTML = `
        <div style="margin-bottom:20px;">
            <img src="${packageData.image_url || 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=600&h=200&fit=crop&auto=format'}" 
                 style="width:100%; height:180px; object-fit:cover; border-radius:12px; margin-bottom:15px;"
                 onerror="this.src='https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=600&h=200&fit=crop&auto=format'">
            <h3 style="font-size:20px; font-weight:700; color:#2c3e50;">${packageData.name || 'Package'}</h3>
            <p style="color:#6c7a8a; font-size:14px;">
                <i class="fas fa-map-marker-alt" style="color:#27ae60;"></i> ${packageData.destination || 'N/A'} 
                ${packageData.package_code ? '| <i class="fas fa-tag"></i> '+packageData.package_code : ''}
            </p>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px;">
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">${packageData.duration_days || 0}</div>
                <div style="font-size:12px; color:#6c7a8a;">Days</div>
            </div>
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">${packageData.duration_nights || 0}</div>
                <div style="font-size:12px; color:#6c7a8a;">Nights</div>
            </div>
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">${packageData.max_people || 0}</div>
                <div style="font-size:12px; color:#6c7a8a;">Max People</div>
            </div>
            <div style="background:#f0fff4; padding:12px; border-radius:8px; text-align:center;">
                <div style="font-size:20px; font-weight:700; color:#27ae60;">LKR ${price}</div>
                <div style="font-size:12px; color:#6c7a8a;">Per Person</div>
            </div>
        </div>
        ${packageData.description ? `
            <div style="margin-bottom:15px;">
                <h4 style="font-weight:600; margin-bottom:6px; color:#2c3e50;">
                    <i class="fas fa-align-left" style="color:#27ae60;"></i> Description
                </h4>
                <p style="font-size:14px; color:#4a5568; line-height:1.6;">${packageData.description}</p>
            </div>` : ''}
        ${packageData.included_services ? `
            <div style="margin-bottom:15px;">
                <h4 style="font-weight:600; margin-bottom:6px; color:#2c3e50;">
                    <i class="fas fa-check-circle" style="color:#27ae60;"></i> Included Services
                </h4>
                <p style="font-size:14px; color:#4a5568; line-height:1.6;">${packageData.included_services}</p>
            </div>` : ''}
        ${packageData.highlights ? `
            <div style="margin-bottom:15px;">
                <h4 style="font-weight:600; margin-bottom:6px; color:#2c3e50;">
                    <i class="fas fa-star" style="color:#f39c12;"></i> Highlights
                </h4>
                <p style="font-size:14px; color:#4a5568; line-height:1.6;">${packageData.highlights}</p>
            </div>` : ''}
        <div style="margin-top:15px; padding-top:15px; border-top:2px solid #f0fff4; text-align:center;">
            <button onclick="document.getElementById('detailsModal').style.display='none'; document.body.style.overflow=''; openBookingModal(${packageData.id})" 
                    class="btn-green" style="width:100%; justify-content:center;">
                <i class="fas fa-calendar-check"></i> Book This Package
            </button>
        </div>
    `;
    document.getElementById('detailsModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// ============================================
// EVENT LISTENERS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - setting up event listeners');
    
    // Form events
    var adultsInput = document.getElementById('bookingAdults');
    var childrenInput = document.getElementById('bookingChildren');
    var insuranceSelect = document.getElementById('insuranceType');
    var vehicleSelect = document.getElementById('vehicleType');
    var promoInput = document.getElementById('promoCodeInput');
    
    if (adultsInput) {
        adultsInput.addEventListener('change', function() {
            if (parseInt(this.value) < 1) this.value = 1;
            calculateTotal();
        });
        adultsInput.addEventListener('input', function() {
            if (parseInt(this.value) < 1) this.value = 1;
            calculateTotal();
        });
    }
    
    if (childrenInput) {
        childrenInput.addEventListener('change', function() {
            if (parseInt(this.value) < 0) this.value = 0;
            calculateTotal();
        });
        childrenInput.addEventListener('input', function() {
            if (parseInt(this.value) < 0) this.value = 0;
            calculateTotal();
        });
    }
    
    if (insuranceSelect) {
        insuranceSelect.addEventListener('change', updateInsurancePrice);
    }
    
    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', toggleVehicleFields);
    }
    
    // Close modal on overlay click
    var bookingModal = document.getElementById('bookingModal');
    if (bookingModal) {
        bookingModal.addEventListener('click', function(e) {
            if (e.target === this) closeBookingModal();
        });
    }
    
    var detailsModal = document.getElementById('detailsModal');
    if (detailsModal) {
        detailsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }
    
    // Enter key for promo
    if (promoInput) {
        promoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyPromoCode();
            }
        });
    }
    
    // ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (bookingState.isModalOpen) closeBookingModal();
            var details = document.getElementById('detailsModal');
            if (details && details.style.display === 'flex') {
                details.style.display = 'none';
                document.body.style.overflow = '';
            }
        }
    });
    
    // ============================================
    // PACKAGE CARD CLICK
    // ============================================
    document.querySelectorAll('.package-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.btn-book-now') || e.target.closest('.btn-view')) return;
            
            var packageId = this.getAttribute('data-package-id');
            if (packageId) {
                this.classList.add('highlight');
                setTimeout(function() { card.classList.remove('highlight'); }, 800);
                openBookingModal(parseInt(packageId));
            }
        });
    });
    
    // ============================================
    // BOOKING FORM SUBMIT - FIXED
    // ============================================
    var bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        console.log('Booking form found, attaching submit handler');
        bookingForm.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            // Let the onsubmit attribute handle validation
            // The validateBookingForm function will be called first
        });
    } else {
        console.error('Booking form not found!');
    }
    
    // Reset state
    bookingState.isProcessing = false;
    var confirmBtn = document.getElementById('confirmBookingBtn');
    if (confirmBtn) {
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm Booking';
        confirmBtn.disabled = false;
    }
    
    console.log('Event listeners setup complete');
});
</script>