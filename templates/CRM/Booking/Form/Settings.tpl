{* ch.ipik.booking — Form/Settings.tpl *}
<div class="crm-container crm-form-block ipik-settings">

  {* ---- Réservation ---- *}
  <h4 class="ipik-h4">{ts}Réservation{/ts}</h4>

  <div class="crm-section">
    <div class="label">{$form.availability_mode.label}</div>
    <div class="content">
      {$form.availability_mode.html}
      <span class="description">
        {ts}<strong>Horaires hebdomadaires</strong> convient aux cabinets à semaine régulière. <strong>Jours de travail déclarés</strong> convient aux équipes dont les disponibilités varient : chaque journée se coche dans un calendrier. Chaque intervenant·e peut utiliser un autre mode que celui-ci.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.default_start_time.label}</div>
    <div class="content">
      {$form.default_start_time.html} &nbsp; {$form.default_end_time.label} {$form.default_end_time.html}
      <span class="description">{ts}Pré-remplissage du calendrier des jours de travail.{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.create_activities.label}</div>
    <div class="content">
      {$form.create_activities.html}
      <span class="description">
        {ts}L'activité relie le rendez-vous à la fiche du patient et le rend visible dans les calendriers CiviCRM. Son sujet ne porte que le type de rendez-vous, jamais le nom du patient. Décochez si la seule existence d'un rendez-vous ne doit pas apparaître dans les calendriers partagés.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.therapist_selector.label}</div>
    <div class="content">
      {$form.therapist_selector.html}
      <span class="description">{ts}Peut être surchargée par type de rendez-vous.{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.slot_interval_minutes.label}</div>
    <div class="content">
      {$form.slot_interval_minutes.html}
      <span class="description">{ts}Ex. 15 → créneaux à 9h00, 9h15, 9h30… Peut être surchargé par type.{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.reminder_hours_before.label}</div>
    <div class="content">
      {$form.reminder_hours_before.html}
      <span class="description">{ts}Nécessite le job planifié « Booking : rappels email ».{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>

  {* ---- Facturation ---- *}
  <h4 class="ipik-h4">{ts}Facturation{/ts}</h4>
  <p class="description">{ts}Valeurs pré-remplies lorsqu'une contribution est saisie depuis un rendez-vous.{/ts}</p>

  <div class="crm-section">
    <div class="label">{$form.contribution_financial_type_id.label}</div>
    <div class="content">{$form.contribution_financial_type_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.contribution_status_id.label}</div>
    <div class="content">{$form.contribution_status_id.html}</div>
    <div class="clear"></div>
  </div>

  {* ---- Envoi des e-mails ---- *}
  <h4 class="ipik-h4">{ts}Envoi des e-mails{/ts}</h4>

  <div class="crm-section">
    <div class="label">{$form.from_email.label}</div>
    <div class="content">
      {$form.from_email.html}
      <span class="description">
        {ts}Expéditeur des confirmations, rappels et annulations. Les adresses proposées sont celles déclarées dans Administration → Communications → Adresses d'expédition. Si l'extension de routage SMTP est installée, ce choix détermine le serveur d'envoi.{/ts}
      </span>
    </div>
    <div class="clear"></div>
  </div>

  {* ---- Agenda CalDAV ---- *}
  <h4 class="ipik-h4">{ts}Agenda CalDAV (Infomaniak){/ts}</h4>
  <p class="description">
    {ts}L'identifiant est le code du compte Infomaniak (ex. FK03484), pas l'adresse email. L'URL d'agenda de chaque intervenant·e se renseigne dans sa fiche.{/ts}
  </p>

  <div class="crm-section">
    <div class="label">{$form.caldav_user.label}</div>
    <div class="content">{$form.caldav_user.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.caldav_password.label}</div>
    <div class="content">
      {$form.caldav_password.html}
      <span class="description">{ts}Laisser vide pour conserver le mot de passe actuel.{/ts}</span>
    </div>
    <div class="clear"></div>
  </div>

  {* État de connexion *}
  {if $syncRows}
    <table class="ipik-sync">
      <tbody>
        {foreach from=$syncRows item=row}
        <tr>
          <td>{$row.name}</td>
          <td>
            {if $row.status eq 'ok'}
              <span class="ipik-ok">{ts}Agenda connecté{/ts}</span>
            {elseif $row.status eq 'error'}
              <span class="ipik-ko">{ts}Agenda injoignable{/ts}</span>
            {elseif $row.status eq 'no_credentials'}
              <span class="ipik-warn">{ts}Identifiants manquants{/ts}</span>
            {else}
              <span class="ipik-muted">{ts}Aucun agenda configuré{/ts}</span>
            {/if}
          </td>
          <td><a href="{$row.edit_url}">{ts}Configurer{/ts}</a></td>
        </tr>
        {/foreach}
      </tbody>
    </table>
  {/if}

  {if $orphanCount}
    <div class="messages status ipik-orphans">
      <strong>{$orphanCount}</strong>
      {ts}événement(s) de rendez-vous annulés subsistent dans les agendas et bloquent des créneaux.{/ts}
      <a href="{$cleanupURL}" class="button" style="margin-left:.6rem"
         onclick="return confirm('{ts}Retirer ces événements des agendas ?{/ts}')">{ts}Nettoyer{/ts}</a>
    </div>
  {/if}

  {* ---- Modèles d'email ---- *}
  <h4 class="ipik-h4">{ts}Modèles d'email{/ts}</h4>
  <p class="description">
    {ts}Contenus modifiables dans les modèles de messages CiviCRM. Tokens propres à l'extension :{/ts}
    <code>{ldelim}booking.type{rdelim}</code>,
    <code>{ldelim}booking.date{rdelim}</code>,
    <code>{ldelim}booking.time{rdelim}</code>,
    <code>{ldelim}booking.duration{rdelim}</code>,
    <code>{ldelim}booking.therapist{rdelim}</code>,
    <code>{ldelim}booking.contact_name{rdelim}</code>,
    <code>{ldelim}booking.location{rdelim}</code>,
    <code>{ldelim}booking.location_address{rdelim}</code>,
    <code>{ldelim}booking.notes{rdelim}</code>,
    <code>{ldelim}booking.cancel_reason{rdelim}</code>.
    {ts}Les tokens CiviCRM standards sont également disponibles{/ts}
    (<code>{ldelim}contact.first_name{rdelim}</code>…).
  </p>

  <table class="ipik-templates">
    <tbody>
      {foreach from=$templateLinks item=tpl}
      <tr>
        <td>{$tpl.title}</td>
        <td>
          {if $tpl.exists}
            <a href="{$tpl.url}">{ts}Modifier{/ts}</a>
          {else}
            <span class="ipik-muted">{ts}Indisponible{/ts}</span>
          {/if}
        </td>
      </tr>
      {/foreach}
    </tbody>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

<style>
.ipik-settings .ipik-h4 {
  margin:1.8rem 0 .4rem; padding-bottom:.4rem;
  border-bottom:1px solid #e5e7eb; font-size:1rem;
}
.ipik-settings .ipik-h4:first-of-type { margin-top:.5rem; }

.ipik-sync, .ipik-templates { border-collapse:collapse; margin:.8rem 0; }
.ipik-sync td, .ipik-templates td { padding:.3rem 1.2rem .3rem 0; }

.ipik-ok    { color:#065f46; }
.ipik-ko    { color:#b91c1c; }
.ipik-warn  { color:#92400e; }
.ipik-muted { color:#9ca3af; }

.ipik-orphans { margin:1rem 0; }
</style>
