{* ch.ipik.booking — Page/ContactAppointments.tpl *}
<div class="crm-container ipik-contact-appts">

  <h3>{ts}Rendez-vous à venir{/ts}</h3>
  {if $upcoming}
    <table class="ipik-appts">
      <tbody>
        {foreach from=$upcoming item=appt}
        {assign var="tcid" value=$appt.therapist_contact_id}
        <tr>
          <td class="ipik-appt__when">
            <div class="ipik-appt__day">{$appt.date_label}</div>
            <div class="ipik-appt__time">{$appt.time_label} – {$appt.end_label}</div>
          </td>
          <td>
            <span class="ipik-dot" style="background:{$appt.type_color}"></span>
            {$appt.type_label}
          </td>
          <td>
            <a href="{crmURL p='civicrm/booking/therapist-agenda' q="cid=`$tcid`&reset=1"}">{$appt.therapist_name}</a>
          </td>
        </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p class="ipik-empty">{ts}Aucun rendez-vous à venir.{/ts}</p>
  {/if}

  <h3 style="margin-top:2rem">{ts}Rendez-vous passés{/ts}</h3>
  {if $past}
    <table class="ipik-appts">
      <tbody>
        {foreach from=$past item=appt}
        {assign var="tcid" value=$appt.therapist_contact_id}
        <tr class="ipik-appt--past">
          <td class="ipik-appt__when">
            <div class="ipik-appt__day">{$appt.date_label}</div>
            <div class="ipik-appt__time">{$appt.time_label} – {$appt.end_label}</div>
          </td>
          <td>
            <span class="ipik-dot" style="background:{$appt.type_color}"></span>
            {$appt.type_label}
          </td>
          <td>
            <a href="{crmURL p='civicrm/booking/therapist-agenda' q="cid=`$tcid`&reset=1"}">{$appt.therapist_name}</a>
          </td>
          <td>
            {if $appt.status eq 'completed'}
              <span class="ipik-badge ipik-badge--done">{ts}Effectué{/ts}</span>
            {/if}
          </td>
        </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p class="ipik-empty">{ts}Aucun rendez-vous passé.{/ts}</p>
  {/if}

</div>

<style>
.ipik-contact-appts .ipik-appts { width:100%; border-collapse:collapse; margin:.6rem 0; }
.ipik-contact-appts .ipik-appts td { padding:.5rem .6rem; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.ipik-contact-appts .ipik-appt__when { width:18rem; }
.ipik-contact-appts .ipik-appt__day  { font-weight:600; text-transform:capitalize; }
.ipik-contact-appts .ipik-appt__time { font-size:.8rem; color:#6b7280; }
.ipik-contact-appts .ipik-appt--past { opacity:.65; }
.ipik-contact-appts .ipik-dot { display:inline-block; width:9px; height:9px; border-radius:50%; margin-right:.4rem; }
.ipik-contact-appts .ipik-empty { color:#9ca3af; font-style:italic; }
.ipik-contact-appts .ipik-badge { border-radius:4px; font-size:.72rem; padding:.15rem .45rem; font-weight:600; background:#dbeafe; color:#1e40af; }
</style>
