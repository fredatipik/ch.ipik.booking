{* ch.ipik.booking — Form/Appointment.tpl *}
<div class="crm-container crm-form-block ipik-new-appt" data-slots-url="{$slotsURL}">

  <div class="crm-section">
    <div class="label">{$form.contact_id.label}</div>
    <div class="content">{$form.contact_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.appointment_type_id.label}</div>
    <div class="content">{$form.appointment_type_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.therapist_id.label}</div>
    <div class="content">{$form.therapist_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.slot_mode.label}</div>
    <div class="content">{$form.slot_mode.html}</div>
    <div class="clear"></div>
  </div>

  {* Créneaux libres *}
  <div class="crm-section" id="ipik-slot-free">
    <div class="label">{$form.slot.label}</div>
    <div class="content">
      {$form.slot.html}
      <span class="description" id="ipik-slot-hint">
        {ts}Choisissez un type et un·e intervenant·e pour voir les créneaux disponibles.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>

  {* Saisie libre *}
  <div class="crm-section" id="ipik-slot-manual" style="display:none">
    <div class="label">{ts}Date et heure{/ts}</div>
    <div class="content">
      <input type="date" name="manual_date" id="ipik-manual-date" class="crm-form-text" />
      <input type="time" name="manual_time" id="ipik-manual-time" class="crm-form-text" step="300" />
      <span class="description">
        {ts}La saisie libre ignore les disponibilités déclarées et les agendas. À réserver aux cas particuliers : le créneau ne sera pas vérifié.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.location_id.label}</div>
    <div class="content">
      {$form.location_id.html}
      <span class="description">
        {ts}Par défaut, le local découle de la journée de travail ou du local habituel de l'intervenant·e. Précisez-le si le rendez-vous se tient ailleurs — l'agenda du local en sera informé.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.notes.label}</div>
    <div class="content">{$form.notes.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.send_notifications.label}</div>
    <div class="content">
      {$form.send_notifications.html}
      <span class="description">{ts}Décochez si la personne a déjà été prévenue.{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
    <a href="{$cancelURL}" class="button cancel">{ts}Annuler{/ts}</a>
  </div>
</div>

{literal}
<script>
(function () {
  var root = document.querySelector('.ipik-new-appt');
  if (!root) return;

  var slotsUrl = root.dataset.slotsUrl;
  var modeEl   = document.querySelector('select[name="slot_mode"]');
  var typeEl   = document.querySelector('select[name="appointment_type_id"]');
  var therEl   = document.querySelector('select[name="therapist_id"]');
  var slotEl   = document.querySelector('select[name="slot"]');
  var hintEl   = document.getElementById('ipik-slot-hint');
  var freeBox  = document.getElementById('ipik-slot-free');
  var manBox   = document.getElementById('ipik-slot-manual');

  function toggleMode() {
    var manual = modeEl.value === 'manual';
    freeBox.style.display = manual ? 'none' : '';
    manBox.style.display  = manual ? '' : 'none';
  }

  function loadSlots() {
    var typeId = typeEl.value;
    var therId = therEl.value;

    slotEl.innerHTML = '';

    if (!typeId || !therId) {
      slotEl.appendChild(new Option('— Choisir un type et un·e intervenant·e —', ''));
      hintEl.textContent = 'Choisissez un type et un·e intervenant·e pour voir les créneaux disponibles.';
      return;
    }

    slotEl.appendChild(new Option('Chargement…', ''));
    hintEl.textContent = '';

    var url = slotsUrl
      + (slotsUrl.indexOf('?') === -1 ? '?' : '&')
      + 'type_id=' + encodeURIComponent(typeId)
      + '&therapist_id=' + encodeURIComponent(therId);

    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        slotEl.innerHTML = '';
        var slots = data.slots || [];

        if (!slots.length) {
          slotEl.appendChild(new Option('Aucun créneau disponible', ''));
          hintEl.textContent = 'Aucune disponibilité sur les deux prochains mois. Utilisez la saisie libre si nécessaire.';
          return;
        }

        slotEl.appendChild(new Option('— Choisir —', ''));
        slots.forEach(function (s) {
          slotEl.appendChild(new Option(s.label, s.value));
        });
        hintEl.textContent = slots.length + ' créneau(x) disponible(s).';
      })
      .catch(function () {
        slotEl.innerHTML = '';
        slotEl.appendChild(new Option('Erreur de chargement', ''));
        hintEl.textContent = 'Les créneaux n\u2019ont pas pu être chargés.';
      });
  }

  modeEl.addEventListener('change', toggleMode);
  typeEl.addEventListener('change', loadSlots);
  therEl.addEventListener('change', loadSlots);

  toggleMode();
  loadSlots();
})();
</script>
{/literal}
