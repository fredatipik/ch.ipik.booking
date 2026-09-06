{* ch.ipik.booking — Form/Therapist.tpl *}
<div class="crm-container crm-form-block">
  <h3>{if $recordId}{ts}Modifier l'intervenant·e{/ts}{else}{ts}Nouvel·le intervenant·e{/ts}{/if}</h3>

  <div class="crm-section">
    <div class="label">{$form.contact_id.label}</div>
    <div class="content">{$form.contact_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.wp_user_id.label}</div>
    <div class="content">
      {$form.wp_user_id.html}
      <span class="description">{ts}Optionnel — ID WordPress de l'utilisateur lié.{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.color.label}</div>
    <div class="content">
      {$form.color.html}
      <input type="color" id="ipik-color-picker" style="margin-left:.5rem;cursor:pointer;" />
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.max_advance_days.label}</div>
    <div class="content">{$form.max_advance_days.html} <span class="description">{ts}jours{/ts}</span></div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.buffer_minutes.label}</div>
    <div class="content">{$form.buffer_minutes.html} <span class="description">{ts}minutes entre deux RDV{/ts}</span></div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.is_active.label}</div>
    <div class="content">{$form.is_active.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.availability_mode.label}</div>
    <div class="content">
      {$form.availability_mode.html}
      <span class="description">
        {ts}<strong>Horaires hebdomadaires</strong> : semaine type régulière, complétée par des congés et des créneaux exceptionnels.{/ts}<br>
        {ts}<strong>Jours de travail déclarés</strong> : chaque journée travaillée se coche dans un calendrier, avec ses horaires et son local.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.default_location_id.label}</div>
    <div class="content">
      {$form.default_location_id.html}
      <span class="description">
        {ts}Lieu de consultation par défaut. En mode « jours de travail », le local déclaré pour une journée l'emporte ; ce réglage sert de repli, notamment pour les rendez-vous posés hors des journées déclarées.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>

  <h4 style="margin-top:1.25rem;border-bottom:1px solid #e5e7eb;padding-bottom:.5rem">{ts}Agenda CalDAV{/ts}</h4>
  <div class="crm-section">
    <div class="label">{$form.calendar_url.label}</div>
    <div class="content">
      {$form.calendar_url.html}
      <span class="description">{ts}URL CalDAV Infomaniak sans ?export. Ex : https://sync.infomaniak.com/calendars/FK03484/aedd0391-...{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>

  {if $agendaURL}
  <h4 style="margin-top:1.25rem;border-bottom:1px solid #e5e7eb;padding-bottom:.5rem">{ts}Agenda{/ts}</h4>
  <div class="crm-section">
    <div class="content">
      <a href="{$agendaURL}" class="button">{ts}Disponibilités, congés et rendez-vous{/ts}</a>
      <span class="description" style="display:block;margin-top:.4rem">
        {ts}Horaires de consultation, périodes de congé et liste des rendez-vous.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>
  {/if}

  <h4 style="margin-top:1.25rem;border-bottom:1px solid #e5e7eb;padding-bottom:.5rem">{ts}Types de rendez-vous proposés{/ts}</h4>
  <div class="crm-section">
    <div class="content">
      {if $assignedTypes}
        {foreach from=$assignedTypes item=tname}
          <div style="margin-bottom:.25rem">• {$tname}</div>
        {/foreach}
        <span class="description">{ts}À modifier depuis le formulaire "Types de rendez-vous".{/ts}</span>
      {else}
        <em>{ts}Aucun type assigné — à configurer depuis "Types de rendez-vous".{/ts}</em>
      {/if}
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
(function() {
  var txt = document.querySelector('input[name="color"]');
  var picker = document.getElementById('ipik-color-picker');
  if (!txt || !picker) return;
  picker.value = txt.value || '#3b82f6';
  picker.addEventListener('input', function() { txt.value = picker.value; });
  txt.addEventListener('input', function() {
    if (/^#[0-9a-f]{6}$/i.test(txt.value)) picker.value = txt.value;
  });
})();
</script>
{/literal}
