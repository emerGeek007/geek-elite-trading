(function () {
  const form = document.getElementById("inscription-form");
  const button = document.getElementById("submit-btn");
  const message = document.getElementById("form-message");
  const dateInput = form.querySelector('input[name="date"]');
  const isLocal = location.hostname === "localhost" || location.hostname === "127.0.0.1";

  const today = new Date();
  const iso = today.toISOString().slice(0, 10);
  dateInput.min = iso;

  function showMessage(text, type) {
    message.hidden = false;
    message.className = "form-message " + type;
    message.textContent = text;
  }

  async function sendLocal(formData) {
    const response = await fetch("send.php", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();
    if (!response.ok || !data.ok) {
      throw new Error(data.error || "L'envoi a échoué.");
    }
    return data;
  }

  async function sendPublic(formData) {
    const payload = {
      nom: formData.get("nom"),
      prenom: formData.get("prenom"),
      email: formData.get("email"),
      phone: formData.get("phone"),
      date: formData.get("date"),
      _subject: "Nouvelle inscription — Geek Elite Trading",
      _template: "table",
      _captcha: "false",
      _replyto: formData.get("email"),
    };

    const response = await fetch("https://formsubmit.co/ajax/emersonfrancois77@gmail.com", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });
    const data = await response.json();
    const success = data.success === true || data.success === "true";
    const activation = String(data.message || "").toLowerCase().includes("activation");

    if (activation) {
      return { ok: true, activation_required: true };
    }
    if (!success) {
      throw new Error(data.message || "L'envoi a échoué.");
    }
    return { ok: true };
  }

  const videoGrid = document.getElementById("video-grid");
  const player = document.getElementById("video-player");
  const lessonVideo = document.getElementById("lesson-video");
  const lessonTitle = document.getElementById("lesson-title");
  const lessonFile = document.getElementById("lesson-file");

  const lightbox = document.getElementById("cert-lightbox");
  const lightboxImg = document.getElementById("cert-lightbox-img");
  const lightboxTitle = document.getElementById("cert-lightbox-title");
  const lightboxClose = document.getElementById("cert-close");
  const slideshow = document.getElementById("certificats");
  const track = document.getElementById("cert-track");
  const originals = track ? Array.from(track.querySelectorAll(".cert-card")) : [];
  const dotsWrap = document.getElementById("cert-dots");
  const SPEED = 22;
  let offset = 0;
  let paused = false;
  let lastTime = 0;

  function stepWidth() {
    const card = track && track.querySelector(".cert-card");
    if (!card) return 0;
    const gap = parseFloat(window.getComputedStyle(track).gap) || 0;
    return card.getBoundingClientRect().width + gap;
  }

  function loopWidth() {
    return stepWidth() * originals.length;
  }

  function wrapOffset() {
    const loop = loopWidth();
    if (loop <= 0) return;
    while (offset >= loop) offset -= loop;
    while (offset < 0) offset += loop;
  }

  function applyOffset() {
    track.style.transform = "translateX(" + -offset + "px)";
    const width = stepWidth();
    const index = width ? Math.round(offset / width) % originals.length : 0;
    dotsWrap.querySelectorAll(".slide-dot").forEach(function (dot, i) {
      dot.classList.toggle("is-active", i === index);
    });
  }

  function nudge(direction) {
    offset += direction * stepWidth();
    wrapOffset();
    applyOffset();
  }

  function tick(now) {
    if (!lastTime) lastTime = now;
    const delta = Math.min(40, now - lastTime);
    lastTime = now;
    const lightboxOpen = lightbox && !lightbox.hidden;
    if (!paused && !lightboxOpen) {
      offset += SPEED * (delta / 1000);
      wrapOffset();
      applyOffset();
    }
    requestAnimationFrame(tick);
  }

  if (originals.length && track && dotsWrap) {
    originals.forEach(function (slide) {
      track.appendChild(slide.cloneNode(true));
    });
    originals.forEach(function (_, i) {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = "slide-dot" + (i === 0 ? " is-active" : "");
      dot.setAttribute("aria-label", "Certificat " + (i + 1));
      dot.addEventListener("click", function () {
        offset = i * stepWidth();
        applyOffset();
      });
      dotsWrap.appendChild(dot);
    });
    document.getElementById("cert-prev").addEventListener("click", function () {
      nudge(-1);
    });
    document.getElementById("cert-next").addEventListener("click", function () {
      nudge(1);
    });
    slideshow.addEventListener("mouseenter", function () {
      paused = true;
    });
    slideshow.addEventListener("mouseleave", function () {
      paused = false;
      lastTime = 0;
    });
    window.addEventListener("resize", function () {
      wrapOffset();
      applyOffset();
    });
    requestAnimationFrame(tick);
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.hidden = true;
    lightboxImg.removeAttribute("src");
    lastTime = 0;
  }

  if (track) {
    track.addEventListener("click", function (event) {
      const button = event.target.closest(".cert-open");
      if (!button) return;
      lightboxImg.src = button.getAttribute("data-src");
      lightboxImg.alt = button.querySelector("img").alt;
      lightboxTitle.textContent = button.getAttribute("data-title") || "";
      lightbox.hidden = false;
    });
  }

  if (lightboxClose) {
    lightboxClose.addEventListener("click", closeLightbox);
  }
  if (lightbox) {
    lightbox.addEventListener("click", function (event) {
      if (event.target === lightbox) closeLightbox();
    });
  }
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") closeLightbox();
    if (lightbox && !lightbox.hidden) return;
    if (event.key === "ArrowLeft") nudge(-1);
    if (event.key === "ArrowRight") nudge(1);
  });

  if (videoGrid && Array.isArray(window.FORMATION_VIDEOS)) {
    window.FORMATION_VIDEOS.forEach(function (lesson, index) {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "video-card";
      button.innerHTML =
        "<small>Leçon " + String(index + 1).padStart(2, "0") + "</small>" +
        "<h3>" + lesson.title + "</h3>" +
        "<p>" + (lesson.size || "Module vidéo") + "</p>";
      button.addEventListener("click", function () {
        document.querySelectorAll(".video-card").forEach(function (card) {
          card.classList.remove("is-active");
        });
        button.classList.add("is-active");
        player.hidden = false;
        lessonTitle.textContent = lesson.title;
        lessonFile.textContent = lesson.file;
        lessonVideo.src = "assets/videos/" + encodeURIComponent(lesson.file);
        lessonVideo.play();
        player.scrollIntoView({ behavior: "smooth", block: "start" });
      });
      videoGrid.appendChild(button);
    });
  }

  const videosSection = document.getElementById("videos");
  const hasVideos = Array.isArray(window.FORMATION_VIDEOS) && window.FORMATION_VIDEOS.length > 0;
  if (!hasVideos) {
    if (videosSection) videosSection.hidden = true;
    document.querySelectorAll('a[href="#videos"]').forEach(function (link) {
      link.hidden = true;
    });
  }

  form.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (!form.reportValidity()) {
      showMessage("Merci de remplir tous les champs correctement.", "err");
      return;
    }

    button.disabled = true;
    button.textContent = "Envoi en cours...";

    try {
      const formData = new FormData(form);
      const data = isLocal ? await sendLocal(formData) : await sendPublic(formData);

      form.reset();
      if (data.activation_required) {
        showMessage("Inscription enregistrée. Confirmez le lien FormSubmit dans Gmail pour activer les emails du site public.", "ok");
      } else {
        showMessage("Demande envoyée. Nous vous recontactons très bientôt.", "ok");
      }
    } catch (error) {
      showMessage(error.message || "Impossible d'envoyer le formulaire.", "err");
    } finally {
      button.disabled = false;
      button.textContent = "Envoyer ma demande";
    }
  });
})();
