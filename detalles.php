<?php
session_start();
require_once 'conexion.php';

// 1. Validar que recibimos un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_propiedad = $_GET['id'];

try {
    // CORRECCIÓN: Cambiamos p.usuario_id por p.agente_id
    $stmt = $conn->prepare("
        SELECT p.*, u.nombre as agente_nombre, u.email as agente_email 
        FROM propiedad p 
        LEFT JOIN usuarios u ON p.agente_id = u.id 
        WHERE p.id = :id
    ");
    $stmt->bindParam(':id', $id_propiedad);
    $stmt->execute();
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si la propiedad no existe, regresamos al index
    if (!$p) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error al cargar la propiedad: " . $e->getMessage());
}

$logeado = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($p['titulo']); ?> - Domu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map_detalle { height: 400px; width: 100%; border-radius: 1rem; z-index: 10; }
        .hero-detail { height: 60vh; min-height: 400px; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <header class="bg-[#111827] text-white py-4 px-6 md:px-10 flex justify-between items-center shadow-lg sticky top-0 z-50">
        <a href="index.php" class="font-bold text-2xl text-white flex items-center gap-2">
            <svg class="w-8 h-8 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Domu
        </a>
        <a href="index.php" class="text-sm font-semibold hover:text-[#6366f1] transition">← Volver al catálogo</a>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-3xl overflow-hidden shadow-2xl bg-gray-200 hero-detail">
                    <?php 
                        $foto_url = (!empty($p['imagen'])) ? 'uploads/' . $p['imagen'] : 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1200&q=80';
                    ?>
                    <img src="<?php echo $foto_url; ?>" class="w-full h-full object-cover" alt="Propiedad">
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
                        <div>
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase mb-2 inline-block">
                                <?php echo htmlspecialchars($p['estado']); ?>
                            </span>
                            <h1 class="text-4xl font-extrabold text-gray-900"><?php echo htmlspecialchars($p['titulo']); ?></h1>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 font-medium">Precio de Venta</p>
                            <p class="text-3xl font-black text-[#6366f1]">$<?php echo number_format($p['precio'], 2); ?> <span class="text-lg">MXN</span></p>
                        </div>
                    </div>

                    <div class="flex gap-6 border-y border-gray-100 py-4 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">📐</span>
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Terreno</p>
                                <p class="font-bold text-gray-800"><?php echo $p['area_m2']; ?> m²</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 border-l pl-6">
                            <span class="text-2xl">📍</span>
                            <div>
                                <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Ubicación</p>
                                <p class="font-bold text-gray-800">Ver mapa abajo</p>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900 mb-3">Descripción de la propiedad</h3>
                    <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                        <?php echo htmlspecialchars($p['descripcion']); ?>
                    </p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        Ubicación Exacta
                    </h3>
                    <div id="map_detalle"></div>
                </div>
            </div> <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-4">Agente Inmobiliario</p>
                        <div class="flex items-center gap-4">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($p['agente_nombre']); ?>&background=6366f1&color=fff" 
                                 class="w-14 h-14 rounded-full border-2 border-indigo-50 shadow-sm">
                            <div>
                                <p class="font-bold text-gray-900 text-lg"><?php echo htmlspecialchars($p['agente_nombre']); ?></p>
                                <p class="text-sm text-indigo-600 font-medium">Asesor Certificado</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-50 space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <span>📧</span> <?php echo htmlspecialchars($p['agente_email']); ?>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <span>✅</span> Propiedad Verificada
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#111827] text-white p-8 rounded-3xl shadow-xl">
                        <h3 class="text-xl font-bold mb-4">¿Te interesa esta propiedad?</h3>
                        <p class="text-gray-400 text-sm mb-6">Déjanos tus datos y un asesor se pondrá en contacto contigo a la brevedad.</p>
                        
                        <form action="#" class="space-y-4">
                            <input type="text" placeholder="Nombre completo" class="w-full bg-gray-800 border-none rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#6366f1]">
                            <input type="email" placeholder="Correo electrónico" class="w-full bg-gray-800 border-none rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#6366f1]">
                            <input type="tel" placeholder="Teléfono" class="w-full bg-gray-800 border-none rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#6366f1]">
                            <textarea placeholder="Hola, me gustaría recibir más información sobre..." rows="3" class="w-full bg-gray-800 border-none rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#6366f1]"></textarea>
                            
                            <button type="button" onclick="alert('¡Gracias! Esta es una función de demostración.')" class="w-full bg-[#6366f1] hover:bg-[#4f46e5] text-white font-bold py-4 rounded-xl transition shadow-lg shadow-indigo-500/20">
                                Enviar Mensaje
                            </button>
                        </form>

                        <div class="mt-6 pt-6 border-t border-gray-800 flex items-center justify-center gap-4">
                            <a href="#" class="text-gray-400 hover:text-green-500 transition">WhatsApp</a>
                            <div class="w-px h-4 bg-gray-700"></div>
                            <a href="#" class="text-gray-400 hover:text-blue-400 transition">Llamar</a>
                        </div>
                    </div>

                    <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-100 flex items-center gap-4">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-xl shadow-sm">🛡️</div>
                        <div>
                            <p class="text-sm font-bold text-indigo-900">Compra Protegida</p>
                            <p class="text-xs text-indigo-700">Verificamos la legalidad de todas nuestras propiedades.</p>
                        </div>
                    </div>
                </div>
            </div> </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 text-center text-sm mt-12">
        <p>© 2026 Domu. Todos los derechos reservados.</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Cargar mapa con las coordenadas de la BD
        const lat = <?php echo $p['latitud'] ?? '20.671956'; ?>;
        const lon = <?php echo $p['longitud'] ?? '-103.348821'; ?>;

        const map = L.map('map_detalle').setView([lat, lon], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // Icono personalizado para el marcador
        const marker = L.marker([lat, lon]).addTo(map)
            .bindPopup("<b><?php echo htmlspecialchars($p['titulo']); ?></b><br>¡Aquí está tu próximo hogar!")
            .openPopup();
    </script>
</body>
</html>