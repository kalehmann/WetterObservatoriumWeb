## Normalisierung von Daten

Alle Daten in dem WetterObservatorium werden als natürliche Zahlen gespeichert.
Einige Daten - wie zum Beispiel die Temperatur in Grad Celsius - werden jedoch
als Dezimalzahl erfasst und können durchaus negativ werden.
Damit die tatsächlich gemessenen Daten in dem System persistiert werden können,
müssen diese vorher in eine ganze Zahl umgewandelt werden.

### Normalisierung des Lufdrucks

Der Luftdruck wird in Hektopascal erfasst.
Zur Normalisierung wird der gemessene Wert mit `10` multipliziert und auf eine
ganze Zahl gerundet.
Damit wird die Speicherung als natürliche Zahl gewährleistet und eine
Nachkommastelle bleibt erhalten.

Um die Luftdruckdaten wieder auszugeben wird der Wert mit `10` dividiert.

### Normalisierung der Luftfeuchtigkeit

Der eingehende Wert der Luftfeuchtigkeit wird mit 10 multipliziert und auf eine
ganze Zahl gerundet.
So ist die Speicherung als natürliche Zahl möglich und die Genauigkeit einer
Nachkommastelle bleibt erhalten.

### Normalisierung der Empfangsfeldstärke

Die Wetterstation kann die Empfangsfeldstärke senden um Probleme mit der WLAN
Verbindung zu diagnostizieren.
Dabei werden die Daten in dbm übermittelt.
Da die vom ESP8266 gemessene RSSI im Bereich von -10 dbm bis -90 dbm liegen
sollte wird vur der Betrag des Messwertes gespeichert.

### Normalisierung der Sonnenintensität

Die Sonnenintensität wird in Lux erfasst und auf eine genze Zahl gerundet.
Eine weitere Normalisierung der Daten ist nicht erforderlich.

### Normalisierung von Temperaturdaten

Eigenhende Temperaturdaten in Grad Celsius werden zuerst durch Addition von
`273.15` in Kelvin umgewandelt.
Anschließend wird der Wert mit `10` multipliziert und auf eine ganze Zahl
gerundet.
Damit wird die Speicherung als natürliche Zahl gewährleistet und eine
Nachkommastelle bleibt erhalten.

Um die Temperaturdaten wieder auszugeben werden die genannten Schritte
in umgekehrter Reihenfolge ausgeführt.
