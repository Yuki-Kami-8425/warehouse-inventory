let slideIndex = 0;
showSlides();

function showSlides() {
    let slides = document.querySelectorAll('.slide');
    let dots = document.querySelectorAll('.dot');

    slides.forEach((slide, index) => {
        slide.style.display = 'none'; // Ẩn tất cả các slide
        dots[index].classList.remove("active"); // Xóa lớp active khỏi tất cả các dấu chấm
    });

    slideIndex++;
    if (slideIndex > slides.length) {
        slideIndex = 1; // Reset lại chỉ số nếu vượt quá số slide
    }

    slides[slideIndex - 1].style.display = 'block'; // Hiện slide hiện tại
    dots[slideIndex - 1].classList.add("active"); // Đánh dấu dấu chấm hiện tại

    setTimeout(showSlides, 5000); // Thay đổi slide mỗi 5 giây
}

function showSlide(index) {
    slideIndex = index; // Đặt chỉ số slide hiện tại
    let slides = document.querySelectorAll('.slide');
    let dots = document.querySelectorAll('.dot');

    slides.forEach(slide => slide.style.display = 'none'); // Ẩn tất cả các slide
    dots.forEach(dot => dot.classList.remove("active")); // Xóa lớp active khỏi tất cả các dấu chấm

    slides[slideIndex - 1].style.display = 'block'; // Hiện slide tương ứng
    dots[slideIndex - 1].classList.add("active"); // Đánh dấu dấu chấm tương ứng
}


function toggleStations() {
    let stationList = document.querySelector('.station-list');
    
    // Nếu danh sách trạm đang mở thì đóng lại, ngược lại thì mở
    if (stationList.classList.contains('open')) {
        stationList.classList.remove('open'); // Đóng danh sách trạm
    } else {
        stationList.classList.add('open'); // Mở danh sách trạm
    }
    showPage('all');
}

function showPage(page) {
    // Ẩn tất cả các phần trước khi hiển thị phần mới
    document.getElementById('home').style.display = 'none'; // Ẩn phần Home
    document.querySelector('.charts').style.display = 'none'; // Ẩn tất cả các biểu đồ và bảng
    document.querySelectorAll('.data-table').forEach(table => {
        table.style.display = 'none'; // Ẩn tất cả các bảng khác
    });
  
    // Kiểm tra xem trang nào đang được yêu cầu
    if (page === 'home') {
        document.getElementById('home').style.display = 'block'; // Hiển thị ảnh chuyển động
    } else if (page === 'all') {
        // Hiển thị bảng tổng hợp cho tất cả các trạm
        document.querySelector('.charts').style.display = 'block'; // Hiển thị biểu đồ tổng hợp
        document.getElementById('all').style.display = 'block'; // Hiển thị bảng dữ liệu cho All
    } else {
        // Nếu là một trạm cụ thể
        document.querySelector('.charts').style.display = 'block'; // Hiển thị biểu đồ cho trạm cụ thể
        document.getElementById(page).style.display = 'block'; // Hiển thị bảng dữ liệu cho trạm cụ thể
    }
}



function updateTime() {
    const now = new Date();
    const optionsDate = { day: 'numeric', month: 'numeric', year: 'numeric' };
    const optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true };
    
    const dateString = now.toLocaleDateString('en-GB', optionsDate); // 19/10/2024
    const timeString = now.toLocaleTimeString('en-US', optionsTime); // 1:52 PM
    
    const sidebar = document.getElementById('sidebar');

    // Khi thanh công cụ mở, hiển thị ngày trước giờ
    if (sidebar.classList.contains('collapsed')) {
        document.getElementById('datetime').innerHTML = `${dateString} ${timeString}`; // Ngày trước, giờ sau
    } else {
        document.getElementById('datetime').innerHTML = `${dateString}<br>${timeString}`; // Ngày trên, giờ dưới
    }
}

function updateTime() {
    const now = new Date();
    const optionsDate = { day: 'numeric', month: 'numeric', year: 'numeric' };
    const optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true };
    
    const dateString = now.toLocaleDateString('en-GB', optionsDate); // 19/10/2024
    const timeString = now.toLocaleTimeString('en-US', optionsTime); // 1:52 PM
    
    const sidebar = document.getElementById('sidebar');
    
    // Khi thanh công cụ mở, hiển thị giờ ở trên và ngày ở dưới
    if (sidebar.classList.contains('collapsed')) {
        document.getElementById('datetime').innerHTML = `${timeString}<br>${dateString}`; // Giờ trên, ngày dưới
    } else {
        document.getElementById('datetime').innerHTML = `${timeString} ${dateString}`; // Cả hai trong một dòng
    }
}

// Cập nhật thời gian mỗi giây
setInterval(updateTime, 1000);
updateTime(); // Gọi ngay lập tức để thiết lập giá trị ban đầu

function toggleSidebar() {
    let sidebar = document.getElementById('sidebar');
    let content = document.querySelector('.content');

    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        content.classList.remove('collapsed');
    } else {
        sidebar.classList.add('collapsed');
        content.classList.add('collapsed');
    }

    updateFooterPosition(); // Cập nhật vị trí của footer sau khi thay đổi thanh công cụ
}

function updateFooterPosition() {
    const sidebar = document.getElementById('sidebar');
    const footer = document.getElementById('datetime');

    // Tính toán chiều rộng thanh công cụ
    const sidebarWidth = sidebar.offsetWidth;

    // Đặt lề trái của footer để căn giữa
    footer.style.left = `calc(${sidebarWidth}px / 2)`; // Căn giữa
    footer.style.transform = 'translateX(-50%)'; // Đẩy về phía bên trái để căn giữa chính xác
}


// Gọi hàm ngay lập tức để thiết lập vị trí ban đầu
updateFooterPosition();
setInterval(updateFooterPosition, 250); // Cập nhật mỗi giây nếu cần thiết

let tooltipTimeout;

document.querySelectorAll('.sidebar ul li a').forEach(item => {
    item.addEventListener('mouseover', function(event) {
        // Xóa timeout trước đó nếu có
        clearTimeout(tooltipTimeout);

        // Tạo tooltip sau 1 giây
        tooltipTimeout = setTimeout(() => {
            showTooltip(event, this); // Hiển thị tooltip
        }, 250); // 1 giây
    });

    item.addEventListener('mouseout', function() {
        clearTimeout(tooltipTimeout); // Xóa timer
        hideTooltip(); // Ẩn tooltip
    });
});

function showTooltip(event, element) {
    const tooltip = document.createElement('div');
    tooltip.classList.add('tooltip');
    tooltip.textContent = element.querySelector('.link-text')?.textContent || 'Tooltip';
    
    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    tooltip.style.top = `${rect.top + window.scrollY - tooltip.offsetHeight - 10}px`; // Vị trí phía trên nút
    tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`; // Căn giữa với nút

    tooltip.classList.add('show');
}

function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove(); // Xóa tooltip khỏi DOM
    }
}


function toggleStations() {
    const stationList = document.getElementById('stationList');
    stationList.classList.toggle('show');
}

function showPage(page) {
    // Logic để hiển thị trang tương ứng
    document.getElementById('content').innerHTML = `<h2>${page.charAt(0).toUpperCase() + page.slice(1)} Page</h2>`;
}

function loadStation(station) {
    fetch(`path/to/your/php/file.php?station=${station}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('content').innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
}

