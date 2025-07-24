document.addEventListener('DOMContentLoaded', () => {
  const canvas = document.getElementById('bubbleCanvas');
  const ctx = canvas.getContext('2d');

  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const particles = [];
  const particleCount = 100;
  const mouse = { x: null, y: null, radius: 100 };

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
      this.size = Math.random() * 5 + 1;
      this.density = Math.random() * 30 + 1;
      this.color = `hsl(${Math.random() * 360}, 100%, 70%)`;
      this.baseX = this.x;
      this.baseY = this.y;
      this.velocity = Math.random() * 0.2 - 0.1;
      this.angle = 0;
    }

    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.closePath();
      ctx.fillStyle = this.color;
      ctx.fill();
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

        if (distance < 100) {
          ctx.strokeStyle = `rgba(255, 255, 255, ${1 - distance / 100})`;
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
});
