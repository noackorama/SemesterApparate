Elektronischer Semester Apparat für Stud.IP
-------------------------------------------

Installation:
ESA besteht aus zwei Plugin Klassen, die über die Webschnittstelle der
Pluginadministration in Stud.IP installiert werden. Dazu müssen alle notwendigen
Dateien in einem zip Archiv zusammengefasst werden. Dieses kann dann direkt über
die vorgesehene Schnittstelle hochgeladen werden.


Konfiguration:
Das Systemplugin zur Erfassung der Semesterapparate kann nur von 'root'
Administratoren oder von beliebigen Nutzern, denen die Rolle 'Literaturadmin'
zugewiesen wurde, benutzt werden. Dazu muss man in der Pluginadministration über
die Rollenadministration eine entsprechende Rolle erzeugen und Nutzern
zuweisen.
Das Plugin kann über folgende Konfigurationseinstellungen gesteuert werden,
diese sind im Stud.IP Administrationsbereich nur für 'root' Administratoren
zugänglich:
ESA_LIT_CATALOG: das Kürzel des für dynamische Listen zu benutzenden
Literaturkataloges
ESA_LIT_CATALOG_SEARCH_FIELD: das Z39.50 Attribut, das zur Suche nach 
dynamischen Listen benutzt wird
ESA_LIT_CATALOG_SEARCH_MAX_HITS: die maximale Anzahl an Einträgen in einer
dynamischen Literaturliste

