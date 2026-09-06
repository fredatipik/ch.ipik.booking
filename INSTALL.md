# ch.ipik.booking v0.4.16

Extension CiviCRM de prise de rendez-vous, conçue pour un cabinet de
plusieurs intervenant·es partageant un ou plusieurs locaux.

## Prérequis

- CiviCRM 5.60+ (testé sur 6.15.4)
- WordPress 6.0+
- PHP 8.1+
- Extension cURL, pour la synchronisation CalDAV

## Installation

```bash
cd ~/sites/[site]/wp-content/uploads/civicrm/ext/
tar xzf ch.ipik.booking-0.4.16.tar.gz
cd ~/sites/[site]/
cv ext:enable ch.ipik.booking
cv flush
```

Tables, migrations et modèles d'e-mail sont mis en place automatiquement.
Lors des mises à jour ultérieures, remplacer les fichiers suffit : le schéma
se met à jour au premier affichage d'une page.

## Configuration

### 1. Paramètres généraux

**Booking → Paramètres**

- *Mode de disponibilité par défaut* — voir la section 4
- *Horaire par défaut* — pré-remplissage du calendrier des jours de travail
- *Stratégie d'attribution* — round-robin, moins chargé, ou aléatoire
- *Intervalle entre créneaux* — surchargeable par type de rendez-vous
- *Rappel e-mail* — délai avant le rendez-vous

### 2. Locaux (facultatif)

**Booking → Locaux → Nouveau local**

Un local représente un lieu de consultation : nom, adresse, couleur, et sa
propre URL CalDAV. Lorsqu'un rendez-vous s'y tient :

- le créneau devient indisponible pour tous les intervenant·es
- un événement est déposé dans l'agenda du local, portant uniquement le nom
  de l'intervenant·e — aucune donnée patient n'y figure
- inversement, tout événement déjà présent dans cet agenda bloque le créneau
  correspondant, ce qui permet de réserver le local sans passer par une
  réservation

Sans local configuré, seuls les agendas personnels sont consultés.

### 3. Intervenant·es

**Booking → Intervenant·es → Nouvel·le intervenant·e**

- Contact CiviCRM lié (obligatoire)
- URL CalDAV de l'agenda personnel
- Mode de disponibilité, ou réglage général
- Tampon entre rendez-vous, horizon de réservation

Les types de rendez-vous se rattachent depuis le formulaire « Types de
rendez-vous » ; la fiche les affiche en lecture seule.

### 4. Disponibilités

Deux modes coexistent. Le mode par défaut se règle dans les Paramètres, et
chaque intervenant·e peut en utiliser un autre depuis sa fiche.

**Horaires hebdomadaires** — pour une semaine type régulière.
Fiche contact → onglet Agenda :

- *Disponibilités récurrentes* : horaires par jour de semaine
- *Congés* : période sans aucun créneau
- *Créneaux exceptionnels* : disponibilité ponctuelle supplémentaire

**Jours de travail déclarés** — pour des disponibilités irrégulières.
Fiche contact → onglet Agenda → *Calendrier des jours de travail* : six mois
s'affichent, chaque journée travaillée se coche d'un clic. Les horaires et le
local se règlent en haut de page et s'appliquent aux clics suivants ; un
second clic retire la journée.

### 5. Types de rendez-vous

**Booking → Types de rendez-vous → Nouveau**

- *Réservable uniquement par contact existant* — réserve le type aux patients
  déjà présents dans CiviCRM
- *Création de compte WordPress obligatoire* — crée un compte au premier
  rendez-vous
- *Intervalle entre créneaux* — vide pour reprendre le réglage général
- Intervenant·es éligibles

### 6. Agenda CalDAV

**Booking → Paramètres → Agenda CalDAV**

| Champ | Valeur |
|---|---|
| Identifiant | Code du compte Infomaniak, ex. `FK03484` — pas l'adresse e-mail |
| Mot de passe | Mot de passe du compte |

L'URL d'agenda se saisit dans la fiche de chaque intervenant·e et de chaque
local. On la trouve dans kSuite → Calendrier → paramètres de l'agenda →
CalDAV. Format attendu :
`https://sync.infomaniak.com/calendars/FK03484/<uuid>`
Les paramètres d'URL (`?export`) sont ignorés automatiquement.

Vérification :

```bash
curl -s -o /dev/null -w "%{http_code}\n" \
  -u "FK03484:MOTDEPASSE" -X PROPFIND \
  "https://sync.infomaniak.com/calendars/FK03484/<uuid>"
# 207 attendu
```

La synchronisation est non bloquante : si CalDAV est indisponible, le
rendez-vous est créé quand même et l'incident est consigné dans le journal
CiviCRM. L'état de chaque agenda s'affiche dans les Paramètres, avec un
bouton de nettoyage si des événements de rendez-vous annulés y subsistent.

### 7. Facturation

**Booking → Paramètres → Facturation**

Type financier et statut de contribution pré-remplis lorsqu'une contribution
est saisie depuis un rendez-vous. Sur la page « Tous les rendez-vous »,
chaque rendez-vous propose deux boutons :

- **Facturer** — formulaire de facture QR (com.ipik.swissQRinvoice)
- **Contribution** — formulaire de contribution CiviCRM

Le contact est passé en paramètre `cid` dans les deux cas.

### 8. Créer un rendez-vous depuis le backoffice

Un bouton *Nouveau rendez-vous* figure sur la page « Tous les rendez-vous »
et sur l'agenda de chaque intervenant·e.

Le patient se cherche parmi les contacts existants, ou se crée à la volée.
L'intervenant·e est pré-sélectionné·e sur la personne connectée lorsqu'elle
est elle-même intervenante ; chacun peut créer un rendez-vous pour un·e
collègue.

L'horaire se fixe de deux façons. *Choisir parmi les créneaux disponibles*
propose les créneaux réellement libres, calculés comme pour une réservation
en ligne. *Saisir une date et une heure* permet de caler un rendez-vous en
dehors des plages déclarées, sans vérification — utile au téléphone, pour
une urgence.

Les e-mails de confirmation partent par défaut, une case permet de s'en
dispenser lorsque la personne a déjà été prévenue de vive voix.

### 9. Formulaire public

Créer une page WordPress contenant :

```
[ipik_booking]
```

Ou, pour pré-sélectionner un type : `[ipik_booking type="3"]`

Pour un visiteur connecté dont le compte WordPress est lié à un contact
CiviCRM, le formulaire est pré-rempli et les données restent modifiables.

### 10. Envoi des e-mails

**Booking → Paramètres → Envoi des e-mails**

L'adresse d'expédition des confirmations, rappels et annulations, choisie
parmi celles déclarées dans **Administration → Communications → Adresses
d'expédition** — la même liste que pour un envoi individuel. Sans choix,
l'adresse par défaut du domaine s'applique.

Cette adresse a une conséquence pratique lorsque `ch.ipik.smtprouter` est
installée : c'est elle qui détermine le serveur SMTP retenu, chaque adresse
Infomaniak exigeant une authentification propre. Une adresse dédiée du type
`rdv@exemple.ch` permet de distinguer ces envois du reste du courrier sortant.

### 11. Modèles d'e-mail

Quatre modèles sont créés automatiquement et modifiables dans
**Administration → Communications → Modèles de messages** :

| Modèle | Destinataire |
|---|---|
| Confirmation de rendez-vous | Patient |
| Nouveau rendez-vous | Intervenant·e |
| Rappel de rendez-vous | Patient |
| Annulation de rendez-vous | Patient et intervenant·e |

Tokens propres à l'extension : `{booking.type}`, `{booking.date}`,
`{booking.time}`, `{booking.duration}`, `{booking.therapist}`,
`{booking.contact_name}`, `{booking.location}`, `{booking.location_address}`,
`{booking.notes}`,
`{booking.cancel_reason}`.
Les tokens CiviCRM standards (`{contact.first_name}`…) fonctionnent aussi.

Les liens d'édition sont regroupés dans Booking → Paramètres.

### 12. Tableau de bord

Un dashlet « Mes rendez-vous » est ajouté au tableau de bord CiviCRM. Il
affiche les dix prochains rendez-vous de la personne connectée, y compris
ceux du jour déjà écoulés.

Ce dashlet ne montre que les rendez-vous dont l'intervenant·e est le contact
CiviCRM de la personne connectée. Un compte administrateur qui n'est pas
enregistré comme intervenant·e ne verra aucun rendez-vous, quelles que
soient ses permissions : les noms de patients n'ont pas à circuler au-delà
de la personne qui les reçoit.

Chacun peut le retirer depuis « Configurer votre tableau de bord », puis le
remettre depuis la liste des dashlets disponibles.

### 13. Rappels par e-mail

**Administration → Tâches planifiées → « Booking : rappels email »**

Désactivé par défaut. Le délai se règle dans les Paramètres.

## Confidentialité

L'extension manipule des données sensibles : un rendez-vous chez un
thérapeute est en soi une information médicale. Quelques garde-fous sont
posés par défaut.

**Le dashlet** ne montre que les rendez-vous de l'intervenant·e connecté·e,
identifié·e par son contact CiviCRM. Un compte administrateur qui n'est pas
enregistré comme intervenant·e ne voit aucun rendez-vous, quelles que soient
ses permissions. Les patients, qui n'ont pas accès à CiviCRM, ne voient rien.

**Les activités CiviCRM** créées pour chaque rendez-vous portent le type de
rendez-vous comme sujet, jamais le nom du patient : ce sujet est repris tel
quel dans les calendriers et les recherches, visibles de toute personne ayant
accès aux activités. L'identité du patient figure dans le champ « cible »,
soumis aux permissions de visibilité des contacts.

Si la seule existence d'un rendez-vous ne doit pas apparaître dans les
calendriers partagés, décochez *Créer une activité CiviCRM pour chaque
rendez-vous* dans les Paramètres. Le rendez-vous reste enregistré et
synchronisé avec les agendas CalDAV, mais ne laisse aucune trace côté
activités — la contrepartie étant qu'il n'apparaît plus non plus dans
l'historique de la fiche patient.

**Les agendas de locaux** ne reçoivent que le nom de l'intervenant·e :
aucune donnée patient n'y figure, ces agendas étant partagés entre toutes
les personnes travaillant dans le lieu.

## Comment un créneau est jugé disponible

Un créneau n'est proposé que si toutes ces conditions sont réunies :

1. l'intervenant·e travaille ce jour-là, sur la plage concernée
2. aucun rendez-vous ne s'y superpose, tampon compris
3. son agenda personnel est libre sur ce créneau
4. le local où il ou elle travaille ce jour-là est libre, le cas échéant

Le local n'est pas choisi à la réservation : il découle de la journée de
travail déclarée. Un intervenant·e travaillant hors local (visio, domicile)
laisse simplement le champ vide, et les agendas de locaux sont alors ignorés.

## Désinstallation

**Administration → Extensions → Booking IPIK → Désinstaller**

Supprime les tables `civicrm_booking_*`, le suivi des migrations, les modèles
d'e-mail et le rôle WordPress `ipik_therapist`.
