/**
 * Edulux Global Sync - 3D Globe & Satellite
 * Powered by Three.js
 */

const initGlobalSync = () => {
    const container = document.getElementById('globe-container');
    if (!container) return;

    const width = container.clientWidth;
    const height = container.clientHeight;

    // 1. Scene & Camera Setup
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.z = 180;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Performance optimization
    container.appendChild(renderer.domElement);

    // 2. The Earth (Neural Network Style)
    const earthGroup = new THREE.Group();
    const geometry = new THREE.SphereGeometry(50, 64, 64);
    const material = new THREE.MeshPhongMaterial({
        color: 0x6366f1,
        wireframe: true,
        transparent: true,
        opacity: 0.3,
    });
    const earth = new THREE.Mesh(geometry, material);
    earthGroup.add(earth);

    // Inner Solid Core for depth
    const coreGeom = new THREE.SphereGeometry(49, 32, 32);
    const coreMat = new THREE.MeshBasicMaterial({ color: 0x0f172a, transparent: true, opacity: 0.6 });
    const core = new THREE.Mesh(coreGeom, coreMat);
    earthGroup.add(core);
    scene.add(earthGroup);

    // 3. The Realistic Satellite
    const satAnchor = new THREE.Group(); // The invisible pivot for orbit
    const satBodyGroup = new THREE.Group(); // The actual satellite parts

    // A. Main Body
    const bodyGeom = new THREE.CylinderGeometry(1.5, 1.5, 5, 8);
    const bodyMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.9, roughness: 0.1 });
    const body = new THREE.Mesh(bodyGeom, bodyMat);
    satBodyGroup.add(body);

    // B. Solar Panels
    const panelGeom = new THREE.BoxGeometry(12, 4, 0.2);
    const panelMat = new THREE.MeshStandardMaterial({ color: 0x1e3a8a, metalness: 0.7, roughness: 0.3 });
    
    const panelLeft = new THREE.Mesh(panelGeom, panelMat);
    panelLeft.position.set(-7, 0, 0);
    satBodyGroup.add(panelLeft);

    const panelRight = new THREE.Mesh(panelGeom, panelMat);
    panelRight.position.set(7, 0, 0);
    satBodyGroup.add(panelRight);

    // C. Antenna Dish (Facing Earth)
    const dishGeom = new THREE.ConeGeometry(2, 1.5, 16, 1, true);
    const dishMat = new THREE.MeshStandardMaterial({ color: 0xcbd5e1, metalness: 0.8, side: THREE.DoubleSide });
    const dish = new THREE.Mesh(dishGeom, dishMat);
    dish.position.set(0, -2.5, 0);
    dish.rotation.x = Math.PI; 
    satBodyGroup.add(dish);

    // Position Satellite in Orbit
    satBodyGroup.position.set(85, 0, 0);
    satAnchor.add(satBodyGroup);
    scene.add(satAnchor);

    // 4. The Data Beam
    const beamGeom = new THREE.BufferGeometry();
    const beamMat = new THREE.LineBasicMaterial({ color: 0x818cf8, transparent: true, opacity: 0.5 });
    let beam = new THREE.Line(beamGeom, beamMat);
    scene.add(beam);

    // 5. Lighting (Essential for Metalness)
    const sunLight = new THREE.DirectionalLight(0xffffff, 1.5);
    sunLight.position.set(5, 3, 5);
    scene.add(sunLight);
    scene.add(new THREE.AmbientLight(0x404040, 0.5));

    // 6. Animation Logic
    const animate = () => {
        requestAnimationFrame(animate);

        earth.rotation.y += 0.0015;
        satAnchor.rotation.y += 0.006;
        satAnchor.rotation.z += 0.002;

        // Keep Satellite oriented towards Earth center
        satBodyGroup.lookAt(0, 0, 0);
        satBodyGroup.rotation.y += Math.PI / 2;

        // Update Beam
        const satPos = new THREE.Vector3();
        body.getWorldPosition(satPos);
        beam.geometry.setFromPoints([satPos, new THREE.Vector3(0, 0, 0)]);
        beam.material.opacity = Math.sin(Date.now() * 0.004) * 0.4 + 0.4;

        renderer.render(scene, camera);
    };

    // 7. Handle Resize
    window.addEventListener('resize', () => {
        const w = container.clientWidth;
        const h = container.clientHeight;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    });

    animate();
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initGlobalSync);