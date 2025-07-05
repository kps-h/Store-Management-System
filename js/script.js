document.getElementById('menu-icon').addEventListener('click', function () {
    document.querySelector('.nav-links').classList.toggle('active');
});
// Get the elements
const menuIcon = document.getElementById('menu-icon');
const navLinks = document.querySelector('.nav-links');

// Add event listener to the menu icon to toggle the visibility of the menu
menuIcon.addEventListener('click', () => {
    // Toggle the 'active' class to show/hide the nav links
    navLinks.classList.toggle('active');
});