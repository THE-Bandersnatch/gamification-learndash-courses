document.addEventListener('DOMContentLoaded', () => {
  const canvas = document.getElementById('bubbleCanvas');
  const ctx = canvas.getContext('2d');

  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const particles = [];
  const particleCount = 150;
  const mouse = { x: null, y: null, radius: 120 };

  window.addEventListener('mousemove', (e) => {
    mouse.x = e.x;
    mouse.y = e.y;
  });

  window.addEventListener('touchmove', (e) => {
    mouse.x = e.touches[0].clientX;
    mouse.y = e.touches[0].clientY;
  });

  window.addEventListener('mouseout', () => {
    mouse.x = null;
    mouse.y = null;
  });

  window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  });

  class Particle {
    constructor() {
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.size = Math.random() * 8 + 3;
      this.density = Math.random() * 30 + 1;
      
      // More vibrant and varied colors like in the screenshot
      const colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
        '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
        '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2'
      ];
      this.color = colors[Math.floor(Math.random() * colors.length)];
      
      this.baseX = this.x;
      this.baseY = this.y;
      this.velocity = Math.random() * 0.3 - 0.15;
      this.angle = 0;
      this.opacity = Math.random() * 0.8 + 0.2;
    }

    draw() {
      ctx.save();
      ctx.globalAlpha = this.opacity;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.closePath();
      ctx.fillStyle = this.color;
      ctx.fill();
      
      // Add a subtle glow effect
      ctx.shadowColor = this.color;
      ctx.shadowBlur = 10;
      ctx.fill();
      ctx.restore();
    }

    update() {
      this.angle += this.velocity;
      this.baseX += Math.cos(this.angle) * 0.5;
      this.baseY += Math.sin(this.angle) * 0.5;

      if (mouse.x && mouse.y) {
        const dx = mouse.x - this.x;
        const dy = mouse.y - this.y;
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (distance < mouse.radius) {
          const forceDirectionX = dx / distance;
          const forceDirectionY = dy / distance;
          const force = (mouse.radius - distance) / mouse.radius * 2;

          this.x -= forceDirectionX * force * this.density;
          this.y -= forceDirectionY * force * this.density;
        } else {
          this.reset();
        }
      } else {
        this.reset();
      }

      this.draw();
    }

    reset() {
      if (this.x !== this.baseX) {
        const dx = this.x - this.baseX;
        this.x -= dx / 10;
      }
      if (this.y !== this.baseY) {
        const dy = this.y - this.baseY;
        this.y -= dy / 10;
      }
    }
  }

  function init() {
    for (let i = 0; i < particleCount; i++) {
      particles.push(new Particle());
    }
  }

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => p.update());
    connect();
    requestAnimationFrame(animate);
  }

  function connect() {
    for (let a = 0; a < particles.length; a++) {
      for (let b = a; b < particles.length; b++) {
        const dx = particles[a].x - particles[b].x;
        const dy = particles[a].y - particles[b].y;
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (distance < 120) {
          const opacity = (1 - distance / 120) * 0.3;
          ctx.strokeStyle = `rgba(139, 195, 74, ${opacity})`;
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(particles[a].x, particles[a].y);
          ctx.lineTo(particles[b].x, particles[b].y);
          ctx.stroke();
        }
      }
    }
  }

  init();
  animate();

  // Timeline interaction functionality
  function initTimeline() {
    // Handle expandable lesson cards
    const lessonCards = document.querySelectorAll('.ldvt-lesson-card[data-expandable="true"]');
    
    lessonCards.forEach(card => {
      const header = card.querySelector('.ldvt-lesson-header');
      const content = card.querySelector('.ldvt-lesson-content');
      
      if (header && content) {
        header.addEventListener('click', function() {
          const isExpanded = card.classList.contains('expanded');
          
          // Close all other cards
          lessonCards.forEach(otherCard => {
            if (otherCard !== card) {
              otherCard.classList.remove('expanded');
            }
          });
          
          // Toggle current card
          if (isExpanded) {
            card.classList.remove('expanded');
          } else {
            card.classList.add('expanded');
          }
        });
      }
    });

    // Add staggered animation delay for roadmap levels
    const roadmapLevels = document.querySelectorAll('.ldvt-roadmap-level');
    roadmapLevels.forEach((level, index) => {
      level.style.animationDelay = `${index * 0.1}s`;
    });

    // Add scroll-triggered animations
    const observerOptions = {
      threshold: 0.2,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in');
        }
      });
    }, observerOptions);

    roadmapLevels.forEach(level => {
      observer.observe(level);
    });

    // Handle start button clicks
    const startButtons = document.querySelectorAll('.ldvt-start-btn');
    startButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.stopPropagation(); // Prevent card expansion
        
        // Add loading state
        const originalText = this.textContent;
        this.textContent = 'Loading...';
        this.style.opacity = '0.7';
        this.style.pointerEvents = 'none';
        
        // The href will handle navigation automatically
        // Reset after delay in case navigation is slow
        setTimeout(() => {
          this.textContent = originalText;
          this.style.opacity = '1';
          this.style.pointerEvents = 'auto';
        }, 3000);
      });
    });

    // Handle legacy timeline items if they exist
    const timelineItems = document.querySelectorAll('.ldvt-timeline-item');
    timelineItems.forEach(item => {
      const lessonLinks = item.querySelectorAll('a[href]');
      lessonLinks.forEach(link => {
        link.addEventListener('click', function (e) {
          const button = this;
          const originalText = button.textContent;
          button.textContent = 'Loading...';
          button.classList.add('ldvt-loading');

          setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('ldvt-loading');
          }, 3000);
        });
      });
    });
  }

  // Initialize timeline functionality when DOM is ready
  if (document.querySelector('.ldvt-lessons-timeline')) {
    initTimeline();
  }
});
