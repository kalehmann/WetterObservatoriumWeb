## Persistenz

Dieses Dokument beschreibt wie Daten in der Anwendung gespeichert werden.

### Erhobene Daten

Folgende Datensätze werden für jede Messgröẞe durch die Anwendung erfasst:

* die Daten der letzten 24 Stunden (minimales Intervall 4 Minuten)
* die Daten der letzten 31 Tage (minimales Intervall 48 Minuten)
* die Daten des letzten Monats (minimales Intervall 48 Minuten)
* die Daten der letzten 365 Tage (minimales Intervall 10 Stunden)
* die Daten des letzten Jahres (minimales Intervall 10 Stunden)

Das minimale Intervall ist jeweils so gewählt, dass die maximale Menge an
gespeicherten Daten vorher bekannt und eine ganze Zahl ist.
