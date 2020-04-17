# Gruppensteuerung
Mithilfe der Gruppensteuerungen können Variablen in Gruppen zusammen geschaltet werden.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Wenn eine Variable in der Gruppe geschaltet wird, werden alle verbleibenden in der Gruppe ebenso geschaltet
* Alle Variablen, welche einer Liste hinzugefügt werden können mit einer seperaten Variable gleichzeitig geschlatet werden

### 2. Vorraussetzungen

- IP-Symcon ab Version 5.0

### 3. Software-Installation

* Über den Module Store das 'Gruppensteuerung'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen: `https://github.com/symcon/Gruppensteuerung`

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Gruppensteuerung'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name      | Beschreibung
--------- | ------------------
Variablen | Die in dieser Liste vorhandenen Variablen gehören zu der Gruppe; Alle Variablen müssen vom gleichen Typ sein und das gleiche Profil haben sowie eine Aktion 

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
Status |variant  | Zeigt den Status der aktuellen Gruppe an

#### Profile

Es werden keine zusätzlichen Profile hinzugefügt.

### 6. WebFront

Hier wird die Statusvariable angezeigt, welche die Gruppe schalten kann.

### 7. PHP-Befehlsreferenze

Es werden keine zusätzlichen Funktionen hinzugefügt.
