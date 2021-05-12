<?php


return [
    'addNew' => 'Új csoport létrehozása',
    'group_head' => 'Csoport alapadatok',
    'name' => 'Csoport neve',
    'editGroup' => 'Csoport szerkesztése',
    'deletegroup' => 'Csoport törlése',
    'notInGroup' => 'Jelenleg még egyetlen csoportnak sem vagy a tagja. Vedd fel a kapcsolatot a gyülekezeted/csoportod felvigyázójával, hogy meg tudjon hívni a helyi csoportba.',
    'areYouSureDelete' => 'Biztosan törlöd ezt a csoportot? A művelet nem vonható vissza! A csoporthoz tartozó minden adat elvész!',
    'groupCreated' => 'A csoport létrejött!',
    'groupUpdated' => 'A csoport sikeresen módosult!',
    'groupDeleted' => 'A csoport törölve lett!',
    'role_head' => 'Jogosultságok leírása',
    'roles' => [
        'member' => 'Csoporttag',           
        'helper' => 'Csoport segítő',     
        'roler' => 'Csoport kisadmin',  
        'admin' => 'Csoport adminisztrátor'
    ],
    'roles_css' => [ //NE FORDÍTSD LE / DO NOT TRANSLATE
        'member' => 'secondary',           
        'helper' => 'info',     
        'roler' => 'success',  
        'admin' => 'primary'
    ],
    'role_helper' => [
        'member' => 'Csak a saját adatait kezelheti',           
        'helper' => 'Kezelheti a többiek adatait is',
        'roler' => 'Kezelheti az adatokat és jogosultságokat is oszthat ki',
        'admin' => 'Bármit csinálhat a csoporttal'
    ],
    'min_publishers' => 'Hírnök száma (legalább)',
    'min_publishers_placeholder' => 'Például: 2',
    'max_publishers' => 'Hírnök száma (maximum)',
    'max_publishers_placeholder' => 'Például: 4',
    'min_time' => 'Legkevesebb eltölthető idő',
    'min_time_options' => [
        30 => 'Fél óra',
        60 => '1 óra',
        120 => '2 óra',
    ],
    'max_time' => 'Legtöbbet eltölthető idő',
    'max_time_options' => [
        60 => '1 óra',
        120 => '2 óra',
        180 => '3 óra',
        240 => '4 óra',
        320 => '5 óra',
    ],
    'max_extend_days' => 'Hány nappal előre foglalhatnak le időpontot?',
    'max_extend_days_placeholder' => 'Például 60',
    'days_head' => 'Szolgálati napok',
    'days' => [
        '1' => 'Hétfő',
        '2' => 'Kedd',
        '3' => 'Szerda',
        '4' => 'Csütörtök',
        '5' => 'Péntek',
        '6' => 'Szombat',
        '0' => 'Vasárnap',
    ],
    'start_time' => 'Szolgálat kezdete',
    'end_time' => 'Szolgálat vége',
    'times' => [
        '0000' => '00:00',
        '0030' => '00:30',
        '0100' => '01:00',
        '0130' => '01:30',
        '0200' => '02:00',
        '0230' => '02:30',
        '0300' => '03:00',
        '0330' => '03:30',
        '0400' => '04:00',
        '0430' => '04:30',
        '0500' => '05:00',
        '0530' => '05:30',
        '0600' => '06:00',
        '0630' => '06:30',
        '0700' => '07:00',
        '0730' => '07:30',
        '0800' => '08:00',
        '0830' => '08:30',
        '0900' => '09:00',
        '0930' => '09:30',
        '1000' => '10:00',
        '1030' => '10:30',
        '1100' => '11:00',
        '1130' => '11:30',
        '1200' => '12:00',
        '1230' => '12:30',
        '1300' => '13:00',
        '1330' => '13:30',
        '1400' => '14:00',
        '1430' => '14:30',
        '1500' => '15:00',
        '1530' => '15:30',
        '1600' => '16:00',
        '1630' => '16:30',
        '1700' => '17:00',
        '1730' => '17:30',
        '1800' => '18:00',
        '1830' => '18:30',
        '1900' => '19:00',
        '1930' => '19:30',
        '2000' => '20:00',
        '2030' => '20:30',
        '2100' => '21:00',
        '2130' => '21:30',
        '2200' => '22:00',
        '2230' => '22:30',
        '2300' => '23:00',
        '2330' => '23:30',
        '2400' => '24:00',
    ],
    'users' => 'Felhasználók',
    'users_helper' => 'Elég az email címet megadnod. Ha nincs még regisztrációja, akkor automatikusan fog neki készülni egy hozzáférés, melyről emailben értesítjük. A nevét, telefonszámát utána kell majd megadnia.',
    'user_add' => 'Hozzáadás',
    'search_placeholder' => 'Minden emailt új sorba írj',
    'note' => 'Megjegyzés',
    'note_helper' => 'A felhasználóhoz írt megjegyzést csak a Csoport admin és a kisadmin látja.',
    'notGroupCreator' => 'Ha csoportokat szeretnél létrehozni, akkor kérjük kérj ehhez jogosultságot az oldal adminisztrátoraitól.',
    'requestButton' => 'Ehhez kattints ide, és töltsd ki az űrlapot.',
    'request' => [
        'title' => 'Csoport létrehozásához jogosultság igénylése',
        'congregation' => 'Gyülekezeted',
        'reason' => 'Miért szeretnél saját csoportot létrehozni?',
        'reason_helper' => 'Pl adott gyülekezetet/csoport kiszolgálásához',
        'info' => 'Kérjük, <strong>csak akkor igényelj csoport létrehozási jogosultságot, ha a gyülekezetedben te vagy megbízva ennek szervezésével</strong>. Egyéb esetben kérjük szólj a gyülekezeted felvigyázóinak, hogy ők igényeljenek ilyen jogosultságot, és utána az email címedet megadásával meg tudnak hívni a csoportba. Fenntartjuk a jogot ahhoz, hogy igénylésedet elutasítsuk. Itt megadott adataidat nem fogjuk tárolni, jelen elbírálás után töröljük.',
        'button' => 'Igénylés beküldése',
        'phoneError' => 'A telefonszámod nincs megadva. Kérjük add meg ezt a Profilom oldalon, és utána küld el az igénylésedet!',
        'sent' => 'Az igénylésedet továbbítottuk az oldal adminisztrátorainak. Kérjük, várd meg a válaszukat!',
    ],
    'requestMail' => [
        'subject' => 'Csoport létrehozási jogosultság igénylése',
        'line_1' => 'Valaki csoport létrehozási jogosultságot igényelt. Adatai: ',
        'line_2' => 'Gyülekezete:',
        'line_3' => 'Az igénylés oka:',
        'line_4' => 'Jogosultságot a Felhasználók menüpontban tudsz neki adni, ha jóváhagyod.'
    ],


];
