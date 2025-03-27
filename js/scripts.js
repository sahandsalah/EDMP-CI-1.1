document.addEventListener("DOMContentLoaded", function() {
    // ========== SIDEBAR TOGGLE ==========
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarToggleClose = document.getElementById('sidebar-toggle-close');
    const sidebar = document.getElementById('sidebar');
    const dashboardContainer = document.querySelector('.dashboard-container');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
        });
    }

    if (sidebarToggleClose) {
        sidebarToggleClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    // Toggle sidebar on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
        }
    });

    // Collapsible sidebar on larger screens
    const collapseSidebar = document.getElementById('collapse-sidebar');
    if (collapseSidebar) {
        collapseSidebar.addEventListener('click', function() {
            dashboardContainer.classList.toggle('sidebar-collapsed');
        });
    }

    // ========== PASSWORD TOGGLE ==========
     const togglePassword = document.querySelector('.toggle-password');
     if (togglePassword) {
         togglePassword.addEventListener('click', function() {
             const passwordInput = document.getElementById('password');
             const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
             passwordInput.setAttribute('type', type);

             // Toggle icon
             const icon = this.querySelector('i');
             icon.classList.toggle('fa-eye');
             icon.classList.toggle('fa-eye-slash');
         });
     }

     // ========== FORM VALIDATION ==========
     const loginForm = document.getElementById('loginForm');
     if (loginForm) {
         loginForm.addEventListener('submit', function(e) {
             e.preventDefault();

             const username = document.getElementById('username').value;
             const password = document.getElementById('password').value;

             // Simple validation
             if (username.trim() === '' || password.trim() === '') {
                 showAlert('Please enter both username and password', 'danger');
                 return;
             }

             // Simulate login success (in a real app, this would be an API call)
             showAlert('Login successful. Redirecting...', 'success');

             // Redirect to dashboard after delay
             setTimeout(() => {
                 window.location.href = 'dashboard.php';
             }, 1500);
         });
     }

     // Alert function for form validation
     function showAlert(message, type) {
         const alertDiv = document.createElement('div');
         alertDiv.className = `alert alert-${type} mt-3 alert-dismissible fade show`;
         alertDiv.role = 'alert';

         alertDiv.innerHTML = `
             ${message}
             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         `;

         const form = document.getElementById('loginForm');
         form.parentNode.insertBefore(alertDiv, form.nextSibling);

         // Auto dismiss after 3 seconds
         setTimeout(() => {
             const bsAlert = new bootstrap.Alert(alertDiv);
             bsAlert.close();
         }, 3000);
     }

     // ========== INITIALIZE CHARTS ==========
     // Revenue Chart
     const revenueChart = document.getElementById('revenueChart');
     if (revenueChart) {
         new Chart(revenueChart, {
             type: 'line',
             data: {
                 labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                 datasets: [{
                     label: 'Revenue',
                     data: [15000, 18000, 16500, 21000, 22500, 25000, 28000, 30000, 29000, 32000, 35000, 38000],
                     backgroundColor: 'rgba(78, 115, 223, 0.05)',
                     borderColor: 'rgba(78, 115, 223, 1)',
                     borderWidth: 2,
                     pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                     pointBorderColor: '#fff',
                     pointHoverRadius: 5,
                     pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                     pointHoverBorderColor: '#fff',
                     pointHitRadius: 10,
                     pointBorderWidth: 2,
                     tension: 0.4,
                     fill: true
                 }, {
                     label: 'Expenses',
                     data: [10000, 11500, 10800, 13000, 14000, 16000, 17500, 19000, 18500, 20000, 22000, 23000],
                     backgroundColor: 'rgba(231, 74, 59, 0.05)',
                     borderColor: 'rgba(231, 74, 59, 1)',
                     borderWidth: 2,
                     pointBackgroundColor: 'rgba(231, 74, 59, 1)',
                     pointBorderColor: '#fff',
                     pointHoverRadius: 5,
                     pointHoverBackgroundColor: 'rgba(231, 74, 59, 1)',
                     pointHoverBorderColor: '#fff',
                     pointHitRadius: 10,
                     pointBorderWidth: 2,
                     tension: 0.4,
                     fill: true
                 }]
             },
             options: {
                 maintainAspectRatio: false,
                 plugins: {
                     legend: {
                         display: true,
                         position: 'top'
                     },
                     tooltip: {
                         backgroundColor: 'rgb(255, 255, 255)',
                         bodyColor: '#858796',
                         titleMarginBottom: 10,
                         titleColor: '#6e707e',
                         titleFontSize: 14,
                         borderColor: '#dddfeb',
                         borderWidth: 1,
                         xPadding: 15,
                         yPadding: 15,
                         displayColors: false,
                         intersect: false,
                         mode: 'index',
                         caretPadding: 10,
                         callbacks: {
                             label: function(context) {
                                 var label = context.dataset.label || '';
                                 if (label) {
                                     label += ': ';
                                 }
                                 if (context.parsed.y !== null) {
                                     label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                 }
                                 return label;
                             }
                         }
                     }
                 },
                 scales: {
                     x: {
                         grid: {
                             display: false,
                             drawBorder: false
                         },
                         ticks: {
                             maxTicksLimit: 12
                         }
                     },
                     y: {
                         ticks: {
                             maxTicksLimit: 5,
                             padding: 10,
                             callback: function(value) {
                                 return '$' + value.toLocaleString();
                             }
                         },
                         grid: {
                             color: "rgb(234, 236, 244)",
                             zeroLineColor: "rgb(234, 236, 244)",
                             drawBorder: false,
                             borderDash: [2],
                             zeroLineBorderDash: [2]
                         }
                     }
                 }
             }
         });
     }

     // Customer Chart
     const customerChart = document.getElementById('customerChart');
     if (customerChart) {
         new Chart(customerChart, {
             type: 'doughnut',
             data: {
                 labels: ['Residential', 'Commercial', 'Industrial', 'Government'],
                 datasets: [{
                     data: [55, 30, 10, 5],
                     backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                     hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
                     hoverBorderColor: "rgba(234, 236, 244, 1)",
                 }]
             },
             options: {
                 maintainAspectRatio: false,
                 plugins: {
                     legend: {
                         position: 'bottom',
                         labels: {
                             usePointStyle: true,
                             padding: 20
                         }
                     },
                     tooltip: {
                         backgroundColor: "rgb(255,255,255)",
                         bodyColor: "#858796",
                         borderColor: '#dddfeb',
                         borderWidth: 1,
                         xPadding: 15,
                         yPadding: 15,
                         displayColors: false,
                         caretPadding: 10,
                     }
                 },
                 cutout: '70%'
             }
         });
     }

     // ========== DROPDOWN FIXES ==========
     // Fix for dropdowns in the navbar
     const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
     const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));

     // ========== TOOLTIPS ==========
     // Enable tooltips everywhere
     const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
     const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

     // ========== CUSTOM SCROLLBAR ==========
     // This would typically use a library like simplebar.js, but left as a placeholder
     // const scrollableElements = document.querySelectorAll('.scrollable');
     // scrollableElements.forEach(el => new SimpleBar(el));

     // ========== NOTIFICATIONS ==========
     // Simulate new notification
     setTimeout(() => {
         const notificationBadge = document.querySelector('.fa-bell + .badge');
         if (notificationBadge) {
             // Increment notification count
             const currentCount = parseInt(notificationBadge.textContent);
             notificationBadge.textContent = currentCount + 1;

             // Flash animation
             notificationBadge.classList.add('animate-pulse');
             setTimeout(() => {
                 notificationBadge.classList.remove('animate-pulse');
             }, 2000);
         }
     }, 60000); // After 1 minute
 });
