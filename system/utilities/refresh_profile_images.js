/**
 * Profile Image Refresh Script
 * This script helps refresh profile images across frames when a user updates their profile picture
 */

// Function to refresh all profile images with a timestamp
function refreshProfileImages() {
    const timestamp = new Date().getTime();
    
    // Update images in current document
    function updateImages(selector) {
        const images = document.querySelectorAll(selector);
        images.forEach(img => {
            let src = img.src.split('?')[0]; // Remove existing query params
            img.src = src + '?v=' + timestamp;
        });
    }
    
    // Update all profile images
    updateImages('.main-header .user-image');
    updateImages('.main-header .profile-img');
    updateImages('.main-sidebar .user-img');
    
    // Try to update parent frame if it exists
    try {
        if (window.parent && window.parent.document) {
            const parentDoc = window.parent.document;
            
            // Update parent images
            const parentHeaderImages = parentDoc.querySelectorAll('.main-header .user-image, .main-header .profile-img');
            const parentSidebarImages = parentDoc.querySelectorAll('.main-sidebar .user-img');
            
            parentHeaderImages.forEach(img => {
                const src = img.src.split('?')[0];
                img.src = src + '?v=' + timestamp;
            });
            
            parentSidebarImages.forEach(img => {
                const src = img.src.split('?')[0];
                img.src = src + '?v=' + timestamp;
            });
        }
    } catch (e) {
        console.log('Could not access parent frame:', e);
    }
    
    // Reload the page after a short delay
    setTimeout(function() {
        window.location.reload();
    }, 1500);
} 