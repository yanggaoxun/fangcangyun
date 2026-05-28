/**
 * 粒子网络动画
 * 墨绿科技风登录页面
 */

(function() {
    const canvas = document.getElementById('particle-canvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // 检测是否为移动设备
    const isMobile = window.innerWidth < 768;
    
    // 配置
    const config = {
        particleCount: isMobile ? 80 : 200,
        connectionDistance: 150,
        mouseDistance: 200,
        particleSpeed: 0.5,
        particleColors: ['#4ade80', '#22c55e', '#16a34a', '#86efac'],
        lineColor: 'rgba(74, 222, 128, 0.15)',
        lineWidth: 1,
        mouseColor: 'rgba(74, 222, 128, 0.3)'
    };
    
    let particles = [];
    let mouse = { x: null, y: null };
    let animationId = null;
    let isVisible = true;
    
    // 设置画布尺寸
    function resizeCanvas() {
        const container = canvas.parentElement;
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;
    }
    
    // 粒子类
    class Particle {
        constructor() {
            this.reset();
        }
        
        reset() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * config.particleSpeed * 2;
            this.vy = (Math.random() - 0.5) * config.particleSpeed * 2;
            this.radius = Math.random() * 2 + 1;
            this.color = config.particleColors[Math.floor(Math.random() * config.particleColors.length)];
            this.opacity = Math.random() * 0.5 + 0.3;
        }
        
        update() {
            // 更新位置
            this.x += this.vx;
            this.y += this.vy;
            
            // 边界检测
            if (this.x < 0 || this.x > canvas.width) {
                this.vx = -this.vx;
            }
            if (this.y < 0 || this.y > canvas.height) {
                this.vy = -this.vy;
            }
            
            // 鼠标交互
            if (mouse.x !== null && mouse.y !== null) {
                const dx = mouse.x - this.x;
                const dy = mouse.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < config.mouseDistance) {
                    const force = (config.mouseDistance - distance) / config.mouseDistance;
                    const angle = Math.atan2(dy, dx);
                    this.vx += Math.cos(angle) * force * 0.02;
                    this.vy += Math.sin(angle) * force * 0.02;
                }
            }
            
            // 限制速度
            const speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
            if (speed > config.particleSpeed * 3) {
                this.vx = (this.vx / speed) * config.particleSpeed * 3;
                this.vy = (this.vy / speed) * config.particleSpeed * 3;
            }
        }
        
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.globalAlpha = this.opacity;
            ctx.fill();
            ctx.globalAlpha = 1;
        }
    }
    
    // 初始化粒子
    function initParticles() {
        particles = [];
        for (let i = 0; i < config.particleCount; i++) {
            particles.push(new Particle());
        }
    }
    
    // 绘制连线
    function drawConnections() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < config.connectionDistance) {
                    const opacity = (1 - distance / config.connectionDistance) * 0.15;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(74, 222, 128, ${opacity})`;
                    ctx.lineWidth = config.lineWidth;
                    ctx.stroke();
                }
            }
            
            // 鼠标连线
            if (mouse.x !== null && mouse.y !== null) {
                const dx = particles[i].x - mouse.x;
                const dy = particles[i].y - mouse.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < config.mouseDistance) {
                    const opacity = (1 - distance / config.mouseDistance) * 0.3;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = `rgba(74, 222, 128, ${opacity})`;
                    ctx.lineWidth = config.lineWidth * 1.5;
                    ctx.stroke();
                }
            }
        }
    }
    
    // 动画循环
    function animate() {
        if (!isVisible) {
            animationId = requestAnimationFrame(animate);
            return;
        }
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // 更新和绘制粒子
        particles.forEach(particle => {
            particle.update();
            particle.draw();
        });
        
        // 绘制连线
        drawConnections();
        
        animationId = requestAnimationFrame(animate);
    }
    
    // 事件监听
    function handleMouseMove(e) {
        const rect = canvas.getBoundingClientRect();
        mouse.x = e.clientX - rect.left;
        mouse.y = e.clientY - rect.top;
    }
    
    function handleMouseLeave() {
        mouse.x = null;
        mouse.y = null;
    }
    
    function handleResize() {
        resizeCanvas();
        initParticles();
    }
    
    // 页面可见性检测
    function handleVisibilityChange() {
        isVisible = !document.hidden;
    }
    
    // 触摸设备支持
    function handleTouchMove(e) {
        if (e.touches.length > 0) {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.touches[0].clientX - rect.left;
            mouse.y = e.touches[0].clientY - rect.top;
        }
    }
    
    function handleTouchEnd() {
        mouse.x = null;
        mouse.y = null;
    }
    
    // 初始化
    function init() {
        resizeCanvas();
        initParticles();
        animate();
        
        // 绑定事件
        canvas.addEventListener('mousemove', handleMouseMove);
        canvas.addEventListener('mouseleave', handleMouseLeave);
        canvas.addEventListener('touchmove', handleTouchMove, { passive: true });
        canvas.addEventListener('touchend', handleTouchEnd);
        window.addEventListener('resize', handleResize);
        document.addEventListener('visibilitychange', handleVisibilityChange);
    }
    
    // 启动
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // 清理函数（页面卸载时）
    window.addEventListener('beforeunload', function() {
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
        canvas.removeEventListener('mousemove', handleMouseMove);
        canvas.removeEventListener('mouseleave', handleMouseLeave);
        canvas.removeEventListener('touchmove', handleTouchMove);
        canvas.removeEventListener('touchend', handleTouchEnd);
        window.removeEventListener('resize', handleResize);
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    });
})();