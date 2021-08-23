## WetterObservatoriumWeb

[![GitHub action status][github_badge]][github_action]
[![GitLab pipeline status][gitlab_badge]][gitlab_pipeline]
[![Drone CI deployment status][drone_badge]][drone_deployment]

### Dokumentation

* [Api](doc/api.md)
- [Normalisierung der Daten](doc/normalization.md)
* [Persistenz](doc/persistenz.md)

### Entwicklung

Zur Entwicklung der Anwendung wird eine Umgebung als [Dockerfile][dockerfile]
definiert.
Zusätzliche Dienste werden in der Datei [`docker-compose.yaml`][dockercompose]
konfiguriert.
Die Umgebung wird mittels
```
docker-compose up
```
gestartet.
Anschließend ist die Anwendung durch einen Nginx-Webserver unter
[`http://localhost:8080`](http://localhost:8080) und durch einen
Apache-Webserver unter
[`http://localhost:8081`](http://localhost:8081) erreichbar.

Um Tests auszuführen kann eine Shell in Docker gestartet werden:
```
$ docker-compose run php sh
# php vendor/bin/phpunit tests
```

  [dockerfile]: Dockerfile
  [dockercompose]: docker-compose.yaml
  [github_action]: https://github.com/kalehmann/WetterObservatoriumWeb/actions/workflows/main.yaml/
  [github_badge]: https://github.com/kalehmann/WetterObservatoriumWeb/actions/workflows/main.yaml/badge.svg
  [gitlab_badge]: https://gitlab.com/kalehmann/WetterObservatoriumWeb/badges/master/pipeline.svg
  [gitlab_pipeline]: https://gitlab.com/kalehmann/WetterObservatoriumWeb/-/pipelines
  [drone_badge]: https://drone.kalehmann.de/api/badges/karsten/WetterObservatoriumWeb/status.svg
  [drone_deployment]: https://drone.kalehmann.de/karsten/WetterObservatoriumWeb
