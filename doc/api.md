## Die Wetter API

### Authentifizierung

Aufgrund der Limitierungen des EPS8266 wird auf die Verwendung von HTTPS
verzichtet.
Stattdessen wird mit der verwendung von HMAC sichergestellt, dass die Daten
tatsächlich
von dem Mikrokontroller stammen.

Um Replay-Angriffen vorzubeugen wird bei jedem Datensatz der aktuelle Zeitstempel
mitgesendet.
Der Server akzeptiert nur Daten mit einer Abweichung von weniger als 10 Sekunden.
Weiterhin werden neue Daten frühestens nach einer Minute aufgenommen.

### Endpunkte

Die API stellt verschiedene Endpunkte bereit.
Im der Tabelle der Endpunkte werden in den einzelnen Pfaden Parameter verwendet.

 Parameter    | Beschreibung
--------------|---------------------------------------------------------------------------------------------------------------------
 `{location}` | Der Ort an dem Werte aufgezeichnet wurden. Zum Beispiel `aquarium` oder `outdoor`. Nur Kleinbuchstaben sind erlaubt.
 `{year}`     | Das Jahr aus welchem die Daten stammen in dem Format `YYYY`.
 `{month}`    | Der Monat aus dem die Daten stammen in dem Format `MM`.
 `{class}`    | Die Messgröße. Zum Beispiel `temperature`.
 `{format}`   | Das Format in dem Daten ausgegeben werden sollen. Zum Beispiel `csv` oder `json`.

### Einspeisung von Daten

Daten werden über den Pfad `/api/{location}` eingespeist.
Um sicherzustellen, dass die Daten aus einer Vertrauenswürdigen Quellen stammen,
werden Requests welche nicht den Anforderungen unter dem Punkt Authentifizierung
genügen abgelehnt.



### Abfrage von Daten

Zur Abfrage von Daten stehen die folgenden Endpunkte zur verfügung:

 Pfad                                              | Methode | Beschreibung
---------------------------------------------------|---------|----------------------------------------------------------------------------------------------------------------------------
 `/api/locations.{format}`                         | `GET`   | Gibt eine Liste mit allen Orten, an denen jemals Werte aufgezeichnet wurden zurück.
 `/api/{location}/classes.{format}`                | `GET`   | Gibt eine Liste mit allen Messgrößen, die jemals an einem Ort erhoben wurden zurück.
 `/api/{location}.{format}`                        | `GET`   | Gibt maximal im Minutentakt alle Messgrößen der letzten 24 Stunden für einen Ort zurück.
 `/api/{location}/31d.{format}`                    | `GET`   | Gibt maximal im Stundentakt alle Messgrößen des letzten Monats mit Durchschnitt, Maximum und Minimum für einen Ort zurück.
 `/api/{location}/31d/{class}.{format}`            | `GET`   | Gibt maximal im Stundentakt eine Messgröße des letzten Monats mit Durchschnitt, Maximum und Minimum für einen Ort zurück.
 `/api/{location}/365d.{format}`                   | `GET`   | Gibt maximal im Tagestakt alle Messgrößen des letzten Jahres mit Durchschnitt, Maximum und Minimum für einen Ort zurück.
 `/api/{location}/365d/{class}.{format}`           | `GET`   | Gibt maximal im Tagestakt eine Messgröße des letzten Jahres mit Durchschnitt, Maximum und Minimum für einen Ort zurück.
 `/api/{location}/{class}.{format}`                | `GET`   | Gibt maximal im Minutentakt eine Messgrößen der letzten 24 Stunden für einen Ort zurück.
 `/api/{location}/{year}.{format}`                 | `GET`   | Gibt maximal im Tagestakt alle Messgrößen des Jahres für einen Ort zurück.
 `/api/{location}/{year}/{class}.{format}`         | `GET`   | Gibt maximal im Tagestakt eine Messgröße des Jahres für einen Ort zurück.
 `/api/{location}/{year}/{month}.{format}`         | `GET`   | Gibt maximal im Stundentakt alle Messgrößen des Monats für einen Ort zurück.
 `/api/{location}/{year}/{month}/{class}.{format}` | `GET`   | Gibt maximal im Stundentakt eine Messgrößen des Monats für einen Ort zurück.
