{* ch.ipik.booking — Page/Dashlet.tpl *}
<div class="ipik-dashlet">

  {if $denied}
    <p class="ipik-dashlet__note">{ts}Vous n'avez pas accès à ces informations.{/ts}</p>

  {elseif !$isTherapist}
    <p class="ipik-dashlet__note">
      {ts}Ce tableau affiche vos rendez-vous. Il reste vide si vous n'êtes pas enregistré·e comme intervenant·e — les rendez-vous des autres ne sont pas visibles ici.{/ts}
    </p>

  {elseif $appointments}
    <table class="ipik-dashlet__list">
      <tbody>
        {foreach from=$appointments item=appt}
        <tr class="{if $appt.is_past}is-past{/if}{if $appt.is_today} is-today{/if}">
          <td class="ipik-dashlet__when">
            <span class="ipik-dashlet__day">{$appt.day}</span>
            <span class="ipik-dashlet__time">{$appt.time}</span>
          </td>
          <td class="ipik-dashlet__what">
            <span class="ipik-dashlet__dot" style="background:{$appt.type_color}"></span>
            <a href="{$appt.url}">{$appt.contact_name}</a>
            <span class="ipik-dashlet__type">{$appt.type_label}</span>
          </td>
          <td class="ipik-dashlet__where">
            {if $appt.location_name}
              <span class="ipik-dashlet__loc" style="border-color:{$appt.location_color}">{$appt.location_name}</span>
            {/if}
          </td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <div class="ipik-dashlet__footer">
      <a href="{$agendaURL}">{ts}Voir tout l'agenda{/ts} →</a>
    </div>

  {else}
    <p class="ipik-dashlet__note">{ts}Aucun rendez-vous à venir.{/ts}</p>
    <div class="ipik-dashlet__footer">
      <a href="{$agendaURL}">{ts}Voir tout l'agenda{/ts} →</a>
    </div>
  {/if}

</div>

<style>
.ipik-dashlet__list { width:100%; border-collapse:collapse; }
.ipik-dashlet__list td { padding:.4rem .5rem; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.ipik-dashlet__list tr.is-past  { opacity:.5; }
.ipik-dashlet__list tr.is-today .ipik-dashlet__day { color:#1d4ed8; font-weight:700; }

.ipik-dashlet__when { white-space:nowrap; width:8.5rem; }
.ipik-dashlet__day  { display:block; font-size:.78rem; color:#6b7280; }
.ipik-dashlet__time { font-weight:600; }

.ipik-dashlet__what a { font-weight:600; }
.ipik-dashlet__type { display:block; font-size:.76rem; color:#6b7280; }
.ipik-dashlet__dot  { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:.35rem; }

.ipik-dashlet__where { text-align:right; white-space:nowrap; }
.ipik-dashlet__loc {
  display:inline-block; font-size:.72rem; padding:.05rem .4rem;
  border:1px solid #d1d5db; border-left-width:3px; border-radius:3px; color:#4b5563;
}

.ipik-dashlet__note   { color:#6b7280; font-size:.85rem; margin:.5rem 0; }
.ipik-dashlet__footer { margin-top:.6rem; font-size:.82rem; }
</style>
