## Normalisierung von Daten

Alle Daten in dem WetterObservatorium werden als natürliche Zahlen gespeichert.
Einige Daten - wie zum Beispiel die Temperatur in Grad Celsius - werden jedoch
als Dezimalzahl erfasst und können durchaus negativ werden.
Damit die tatsächlich gemessenen Daten in dem System persistiert werden können,
müssen diese vorher in eine ganze Zahl umgewandelt werden.

### Normalisierung von Temperaturdaten

Eigenhende Temperaturdaten in Grad Celsius werden zuerst durch Addition von
`273.15` in Kelvin umgewandelt.
Anschließend wird der Wert mit `10` multipliziert und auf eine ganze Zahl
gerundet.
Damit wird die Speicherung als natürliche Zahl gewährleistet und eine
Nachkommastelle bleibt erhalten.

Um die Temperaturdaten wieder auszugeben werden die genannten Schritte
in umgekehrter Reihenfolge ausgeführt.

### Normalisierung der Luftfeuchtigkeit

Der eingehende Wert der Luftfeuchtigkeit wird mit 10 multipliziert und auf eine
ganze Zahl gerundet.
So ist die Speicherung als natürliche Zahl möglich und die Genauigkeit einer
Nachkommastelle bleibt erhalten.

### Normalisierung der Sonnenintensität

Die Sonnenintensität wird in Lux erfasst und auf eine genze Zahl gerundet.
Eine weitere Normalisierung der Daten ist nicht erforderlich.

### Normalisierung des Lufdrucks

Der Luftdruck wird in Hektopascal erfasst.
Zur Normalisierung wird der gemessene Wert mit `10` multipliziert und auf eine
ganze Zahl gerundet.
Damit wird die Speicherung als natürliche Zahl gewährleistet und eine
Nachkommastelle bleibt erhalten.

Um die Luftdruckdaten wieder auszugeben wird der Wert mit `10` dividiert.
