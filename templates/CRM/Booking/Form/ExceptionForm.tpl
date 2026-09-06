{* ch.ipik.booking — Form/ExceptionForm.tpl *}
<div class="crm-container crm-form-block">
  <h3>{ts}Congé ou créneau exceptionnel{/ts}</h3>
  <p class="description">
    {ts}Un <strong>congé</strong> retire tous les créneaux de la période. Un <strong>créneau exceptionnel</strong> ajoute une disponibilité ponctuelle, en plus des horaires habituels.{/ts}
  </p>

  <div class="crm-section">
    <div class="label">{$form.date_start.label}</div>
    <div class="content">
      <input type="date" name="date_start" id="ipik_date_start" class="crm-form-text required" required />
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.date_end.label}</div>
    <div class="content">
      <input type="date" name="date_end" id="ipik_date_end" class="crm-form-text required" required />
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.type.label}</div>
    <div class="content">{$form.type.html}</div>
    <div class="clear"></div>
  </div>

  <div id="ipik-special-times" style="display:none">
    <div class="crm-section">
      <div class="label">{$form.start_time.label}</div>
      <div class="content">
        <input type="time" name="start_time" id="ipik_start_time" class="crm-form-text" />
      </div>
      <div class="clear"></div>
    </div>
    <div class="crm-section">
      <div class="label">{$form.end_time.label}</div>
      <div class="content">
        <input type="time" name="end_time" id="ipik_end_time" class="crm-form-text" />
      </div>
      <div class="clear"></div>
    </div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.note.label}</div>
    <div class="content">{$form.note.html}</div>
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
  var typeSelect = document.querySelector('select[name="type"]');
  var timesBlock = document.getElementById('ipik-special-times');
  if (!typeSelect || !timesBlock) return;

  function toggleTimes() {
    timesBlock.style.display = (typeSelect.value === 'special') ? '' : 'none';
  }
  typeSelect.addEventListener('change', toggleTimes);
  toggleTimes();
})();
</script>
{/literal}
