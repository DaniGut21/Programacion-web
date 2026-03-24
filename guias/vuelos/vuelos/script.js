const form = document.getElementById("passengerForm");
const flightsGrid = document.getElementById("flightsGrid");
const passengerPreview = document.getElementById("passengerPreview");
const bookingModal = document.getElementById("bookingModal");
const bookingSummary = document.getElementById("bookingSummary");
const bookingTitle = document.getElementById("bookingTitle");
const closeModal = document.getElementById("closeModal");

const availableFlights = [
  {
    id: 1,
    destino: "Medellin, Colombia",
    fecha: "2026-04-10",
    hora: "07:20",
    aerolinea: "Avianca",
    precio: "$ 320.000 COP"
  },
  {
    id: 2,
    destino: "Cartagena, Colombia",
    fecha: "2026-04-12",
    hora: "12:45",
    aerolinea: "LATAM",
    precio: "$ 410.000 COP"
  },
  {
    id: 3,
    destino: "San Andres, Colombia",
    fecha: "2026-04-20",
    hora: "15:15",
    aerolinea: "Wingo",
    precio: "$ 530.000 COP"
  },
  {
    id: 4,
    destino: "Ciudad de Mexico, Mexico",
    fecha: "2026-05-03",
    hora: "09:00",
    aerolinea: "Aeromexico",
    precio: "$ 1.250.000 COP"
  },
  {
    id: 5,
    destino: "Madrid, Espana",
    fecha: "2026-05-15",
    hora: "21:40",
    aerolinea: "Iberia",
    precio: "$ 2.800.000 COP"
  }
];

if (form) {
  form.addEventListener("submit", (event) => {
    event.preventDefault();

    const formData = new FormData(form);
    const passengerData = {
      nombre: formData.get("nombre")?.toString().trim(),
      apellido: formData.get("apellido")?.toString().trim(),
      sexo: formData.get("sexo")?.toString().trim(),
      edad: formData.get("edad")?.toString().trim(),
      correo: formData.get("correo")?.toString().trim(),
      profesion: formData.get("profesion")?.toString().trim()
    };

    localStorage.setItem("passengerData", JSON.stringify(passengerData));
    window.location.href = "vuelos.html";
  });
}

if (flightsGrid) {
  const storedData = localStorage.getItem("passengerData");
  const passengerData = storedData ? JSON.parse(storedData) : null;

  if (!passengerData) {
    window.location.href = "index.html";
  } else {
    passengerPreview.textContent = `Pasajero: ${passengerData.nombre} ${passengerData.apellido} - Salida desde Bogota`;
    renderFlights(passengerData);
  }
}

function renderFlights(passengerData) {
  flightsGrid.innerHTML = "";

  availableFlights.forEach((flight, index) => {
    const card = document.createElement("article");
    card.className = "flight-card";
    card.style.animationDelay = `${index * 0.08}s`;

    card.innerHTML = `
      <h3 class="flight-route">Bogota -> ${flight.destino}</h3>
      <p class="flight-meta">Aerolinea: ${flight.aerolinea}</p>
      <p class="flight-meta">Fecha: ${flight.fecha}</p>
      <p class="flight-meta">Hora: ${flight.hora}</p>
      <p class="flight-price">${flight.precio}</p>
      <button class="btn-flight" data-flight-id="${flight.id}">Reservar cupo</button>
    `;

    flightsGrid.appendChild(card);
  });

  flightsGrid.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-flight-id]");
    if (!button) {
      return;
    }

    const selectedFlight = availableFlights.find((item) => item.id === Number(button.dataset.flightId));
    if (!selectedFlight) {
      return;
    }

    showBookingSummary(passengerData, selectedFlight);
  });
}

function showBookingSummary(passengerData, selectedFlight) {
  const bookingCode = `RV-${Math.floor(1000 + Math.random() * 9000)}`;

  if (bookingTitle) {
    bookingTitle.textContent = `Reserva realizada: Bogota -> ${selectedFlight.destino}`;
  }

  bookingSummary.innerHTML = `
    <p><strong>Codigo de reserva:</strong> ${bookingCode}</p>
    <p><strong>Nombre:</strong> ${passengerData.nombre} ${passengerData.apellido}</p>
    <p><strong>Sexo:</strong> ${passengerData.sexo}</p>
    <p><strong>Edad:</strong> ${passengerData.edad}</p>
    <p><strong>Correo:</strong> ${passengerData.correo}</p>
    <p><strong>Profesion:</strong> ${passengerData.profesion}</p>
    <p><strong>Ruta:</strong> Bogota -> ${selectedFlight.destino}</p>
    <p><strong>Aerolinea:</strong> ${selectedFlight.aerolinea}</p>
    <p><strong>Fecha:</strong> ${selectedFlight.fecha}</p>
    <p><strong>Hora:</strong> ${selectedFlight.hora}</p>
    <p><strong>Precio:</strong> ${selectedFlight.precio}</p>
  `;

  bookingModal.classList.add("show");
  bookingModal.setAttribute("aria-hidden", "false");
}

if (closeModal && bookingModal) {
  closeModal.addEventListener("click", () => {
    bookingModal.classList.remove("show");
    bookingModal.setAttribute("aria-hidden", "true");
  });

  bookingModal.addEventListener("click", (event) => {
    if (event.target === bookingModal) {
      bookingModal.classList.remove("show");
      bookingModal.setAttribute("aria-hidden", "true");
    }
  });
}
