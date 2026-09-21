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
