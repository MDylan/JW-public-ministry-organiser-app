<?php

return [
    'languages' => [
        'title' => 'Elérhető nyelvek',
        'country_code' => 'Országkód',
        'country_name' => 'Nyelv (Ez jelenik meg a választólistában.)',
        'empty'        => 'Még nem adtál hozzá nyelvet.',
        'default'   => 'Alapértelmezett nyelv',
        'lang_help' => 'A nyelvválasztó legalább 2 elérhető nyelv esetén jelenik meg.<br/>
                        Meglévő nyelv módosításához írd be újra az országkódot és a módosítandó szöveget.
                        <hr>
                        Mielőtt elérhetővé teszel egy új nyelvet, kérjük győződj meg róla, hogy a nyelvi fájlok elérhetőek a "/resources/lang" mappában. <br/>
                        Ha az adott nyelven nincs lefordítva valami, akkor ettől még használható lesz az oldal, de nem fog megjelenni értelmezhető szöveg a tartalom helyén.<br/>
                        Bővebb információért lásd a <a class="alert-link" href="https://laravel.com/docs/8.x/localization" target="_blank">laravel dokumentációt</a>.',
        'success' => 'A nyelv hozzá lett adva',
        'confirmDelete' => [
            'question'  => 'Biztosan törlöd ezt a nyelvet?',
            'message'   => 'Ezt követően a nyelv nem lesz elérhető senki számára.',
            'success'   => 'Sikeresen törölted a nyelvet.'
        ],
        'defaultSet' => [
            'success'   => 'Az új alapértelmezett nyelv beállítva',
            'error'     => 'Nem létezik ez a nyelv a nyelvek listájában!',
        ]
    ],
    'others' => [
        'title' => 'Egyéb beállítások',
        'registration'  => 'Regisztrálás lehetősége',
        'claim_group_creator' => 'Csoport létrehozó jogosultság igényelhető'
    ],
    'others_saved'  => 'A beállítások sikeresen mentve lettek.'
];