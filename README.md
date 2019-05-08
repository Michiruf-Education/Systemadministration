# TODOs

## Docker
MUSS / KANN:
* ~~Catch-All~~
* *~~Bei Diensten registrieren (mit Catch-All-Adressen)~~*
* OpenRelay konfigurieren, OpenSMPTD ist da (Absprache mit Eggi wäre da gut, damit wir da auf der sicheren Seite sind)
* ~~Seite Aufsetzen, entweder plain html, ganz schäbig, oder paar Sample Inhalte in Wordpress klatschen (da gibts auch Demo Seiten von Templates, die einem da bisschen Content reinmachen)~~
* ~~Mail-Adresse auf der Seite anzeigen~~
* Seite indexieren lassen, wo auch immer (google duckduckgo, ...)
* Hook für eingehende Mails in separatem Container
* Hook für ausgehende Mails in separatem Container
* Datenbank-Server einrichten
* Open-Relay publizieren
* *Ganz wichtig: aktuellen Eingangsserver so konfigurieren, dass auch jeglicher Spam ankommt (dafür müssen evtl. standardmäßig aktive Module noch deaktiviert werden)*
* Für den Ausgangsserver evtl. mit DKIM einrichten, damit der als authentisch gehalten wird (weiß nicht ob notwendig)
* Auf eingegangene Spam-Mails antworten in separatem Container (würde ich dann über die Datenbank machen, damit man flags setzen kann was erledigt wurde)
* In eingegangenen Spam-Mails die Links anklicken (ebenfalls über Datenbank)
* Falls wir möchten noch einen /wp-login Honeypot (denke das ist einfach ganz nett und easy, skaliert quasi auch mit der "Indexierung")
* Rausfinden, was man analysieren kann
* Evtl. schaltet man sogar Spam-Module beim Eingangsserver ein, um Spam-Mails klassifizieren zu lassen und dann nur auf diese zu reagieren (Problem könnte sein, dass man auf ne Registrierungsmail "ich möchte mich wieder abmelden" klickt)
* Mails in entsprechende Unterordner anhand von Prefix verschieben

Einiges davon ist ganz klar ein KÖNNTE.
