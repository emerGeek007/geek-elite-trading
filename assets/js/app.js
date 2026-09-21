(function () {
  const form = document.getElementById("inscription-form");
  const button = document.getElementById("submit-btn");
  const message = document.getElementById("form-message");
  const dateInput = form.querySelector('input[name="date"]');

  const today = new Date();
  const iso = today.toISOString().slice(0, 10);
  dateInput.min = iso;

  function showMessage(text, type) {
    message.hidden = false;
    message.className = "form-message " + type;
    message.textContent = text;
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
      const response = await fetch("send.php", {
        method: "POST",
        body: new FormData(form),
      });
      const data = await response.json();

      if (!response.ok || !data.ok) {
        throw new Error(data.error || "L'envoi a échoué.");
      }

      form.reset();
      if (data.activation_required) {
        showMessage("Inscription enregistrée. Activez votre email Gmail via le lien FormSubmit, puis les prochaines demandes arriveront en temps réel.", "ok");
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
