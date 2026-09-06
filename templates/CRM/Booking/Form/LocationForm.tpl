{* ch.ipik.booking — Form/LocationForm.tpl *}
<div class="crm-container crm-form-block">
  <h3>{if $recordId}{ts}Modifier le local{/ts}{else}{ts}Nouveau local{/ts}{/if}</h3>

  <div class="crm-section">
    <div class="label">{$form.name.label}</div>
    <div class="content">{$form.name.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.address.label}</div>
    <div class="content">{$form.address.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.color.label}</div>
    <div class="content">
      {$form.color.html}
      <input type="color" id="ipik-color-picker" style="margin-left:.5rem;cursor:pointer" />
      <span class="description">{ts}Sert à distinguer les locaux dans le calendrier des jours de travail.{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.calendar_url.label}</div>
    <div class="content">
      {$form.calendar_url.html}
      <span class="description">
        {ts}URL CalDAV de l'agenda du local, sans paramètre. Les rendez-vous y sont déposés sous le seul nom de l'intervenant·e, et les événements qui s'y trouvent bloquent les créneaux correspondants.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.weight.label}</div>
    <div class="content">{$form.weight.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.is_active.label}</div>
    <div class="content">{$form.is_active.html}</div>
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
  var txt    = document.querySelector('input[name="color"]');
  var picker = document.getElementById('ipik-color-picker');
  if (!txt || !picker) return;
  picker.value = txt.value || '#8b5cf6';
  picker.addEventListener('input', function () { txt.value = picker.value; });
  txt.addEventListener('input', function () {
    if (/^#[0-9a-f]{6}$/i.test(txt.value)) picker.value = txt.value;
  });
})();
</script>
{/literal}
