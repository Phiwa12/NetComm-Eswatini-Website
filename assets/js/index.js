// Slideshow functionality with pause
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('.indicator');
        const totalSlides = slides.length;
        let slideshowInterval;
        const slideshowWrapper = document.querySelector('.slideshow-wrapper');
        //let isPaused = false;

        // Initialize slideshow
        function initSlideshow() {
            showSlide(currentSlideIndex);
            startSlideshow();
        }
        
        function showSlide(index) {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(indicator => indicator.classList.remove('active'));
            
            // Show current slide
            slides[index].classList.add('active');
            indicators[index].classList.add('active');
            
            // Reset animations for slide 6 (index 5)
            if(index === 5) {
                resetSlide6Animations();
            }
        }

        function nextSlide() {
            if(isPaused) return;
            
            currentSlideIndex = (currentSlideIndex + 1) % totalSlides;
            showSlide(currentSlideIndex);
        }

        function prevSlide() {
            if(isPaused) return;
            
            currentSlideIndex = (currentSlideIndex - 1 + totalSlides) % totalSlides;
            showSlide(currentSlideIndex);
        }

        function changeSlide(direction) {
            if (direction === 1) {
                nextSlide();
            } else {
                prevSlide();
            }
        }

        function currentSlide(index) {
            if(isPaused) return;
            
            currentSlideIndex = index - 1;
            showSlide(currentSlideIndex);
        }
        
        // Reset animations for slide 6
        function resetSlide6Animations() {
            const listItems = document.querySelectorAll('.features-list li');
            listItems.forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
            });
            
            // Re-apply animations after a short delay
            setTimeout(() => {
                listItems.forEach((item, i) => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                });
            }, 100);
        }

        // Start the slideshow
        function startSlideshow() {
            if(slideshowInterval) {
                clearInterval(slideshowInterval);
            }
            slideshowInterval = setInterval(nextSlide, 5000);
            isPaused = false;
            slideshowWrapper.classList.remove('paused');
        }
        
        // Pause the slideshow
        //function pauseSlideshow() {
            //clearInterval(slideshowInterval);
            //isPaused = true;
            //slideshowWrapper.classList.add('paused');
       // }
        
        // Toggle pause state
        function togglePause() {
            if(isPaused) {
                startSlideshow();
            } else {
                pauseSlideshow();
            }
        }
        
        // Pause when clicking on the slideshow wrapper
        slideshowWrapper.addEventListener('click', function(e) {
            // Don't pause if clicking on navigation buttons
            if(e.target.closest('.slideshow-nav')) return;
            
            // Don't pause if clicking on a link/button inside the slide
            if(e.target.closest('a')) return;
            
            pauseSlideshow();
        });

        // Initialize slideshow
        initSlideshow();

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.backgroundColor = 'rgba(30, 58, 138, 0.95)';
            } else {
                navbar.style.backgroundColor = 'var(--navy-blue)';
            }
        });

        // Add loading fallback for images
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.brand-image');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    // Create a placeholder if image fails to load
                    const brandName = this.alt.replace(' Logo', '');
                    this.src = `https://via.placeholder.com/80x60/60a5fa/ffffff?text=${encodeURIComponent(brandName)}`;
                });
            });
        });