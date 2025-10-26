<?php
function create_care_article(PDO $pdo, array $data): int
{
    $title = trim($data['title'] ?? '');
    $slugInput = trim($data['slug'] ?? '');
    $slug = $slugInput !== '' ? $slugInput : slugify($title);
    $slug = ensure_unique_slug($pdo, 'care_articles', $slug);
    $stmt = $pdo->prepare('INSERT INTO care_articles(title, slug, summary, content, is_published) VALUES (:title, :slug, :summary, :content, :is_published)');
    $stmt->execute([
        'title' => $title,
        'slug' => $slug,
        'summary' => $data['summary'] ?? null,
        'content' => $data['content'],
        'is_published' => !empty($data['is_published']) ? 1 : 0,
    ]);
    return (int)$pdo->lastInsertId();
}

function update_care_article(PDO $pdo, int $id, array $data): void
{
    $title = trim($data['title'] ?? '');
    $slugInput = trim($data['slug'] ?? '');
    $slug = $slugInput !== '' ? $slugInput : slugify($title);
    $slug = ensure_unique_slug($pdo, 'care_articles', $slug, $id);
    $stmt = $pdo->prepare('UPDATE care_articles SET title = :title, slug = :slug, summary = :summary, content = :content, is_published = :is_published, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        'title' => $title,
        'slug' => $slug,
        'summary' => $data['summary'] ?? null,
        'content' => $data['content'],
        'is_published' => !empty($data['is_published']) ? 1 : 0,
        'id' => $id,
    ]);
}

function delete_care_article(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM care_articles WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function get_care_articles(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM care_articles ORDER BY title ASC')->fetchAll();
}

function get_published_care_articles(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM care_articles WHERE is_published = 1 ORDER BY title ASC')->fetchAll();
}

function get_care_article(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM care_articles WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $article = $stmt->fetch();
    return $article ?: null;
}

function get_care_article_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM care_articles WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $article = $stmt->fetch();
    return $article ?: null;
}

function ensure_default_care_articles(PDO $pdo): void
{
    $defaults = [
        'pogona-vitticeps' => [
            'title' => 'Pogona vitticeps – umfassender Pflegeleitfaden',
            'summary' => 'Kompletter Praxisleitfaden für die artgerechte Haltung, Ernährung, Technik und Gesundheitsvorsorge von Bartagamen (Pogona vitticeps).',
            'content' => <<<HTML
<h2>Steckbrief</h2>
<p><strong>Wissenschaftlicher Name:</strong> <em>Pogona vitticeps</em><br>
<strong>Umgangssprache:</strong> Bartagame, Inland-Bearded Dragon<br>
<strong>Herkunft:</strong> Halbwüsten und Savannen Zentralaustraliens<br>
<strong>Lebenserwartung:</strong> 10–14 Jahre bei konsequenter Pflege</p>

<h2>Terrarium und Technik</h2>
<p>Für adulte Tiere wird ein Terrarium von mindestens <strong>150 × 80 × 80&nbsp;cm</strong> empfohlen. Jungtiere starten in 100 × 60 × 60&nbsp;cm, damit sie Futter sicher finden. Arbeiten Sie mit Rückwandmodulen, erhöhten Sonnenplätzen und mehreren Höhlen.</p>
<ul>
    <li><strong>Substrat:</strong> Grabfähiges Lehm-Sand-Gemisch (60&nbsp;% Spielsand, 40&nbsp;% Lehm), teilweise mit Steinplatten kombiniert.</li>
    <li><strong>Klima:</strong> Tagesluftfeuchte 30–40&nbsp;%, nachts bis 50&nbsp;% durch gezieltes Sprühen.</li>
    <li><strong>Struktur:</strong> Astgabeln, Plateaus und Sichtschutz, um thermische Mikroklimata zu schaffen.</li>
</ul>

<h3>Beleuchtung und Temperatur</h3>
<table>
    <thead>
        <tr><th>Zone</th><th>Temperatur</th><th>Technik</th></tr>
    </thead>
    <tbody>
        <tr><td>Sonnenplatz</td><td>45–50&nbsp;°C</td><td>HQI oder Metalldampflampe (70–100&nbsp;W) plus Halogen-Spot</td></tr>
        <tr><td>Grundtemperatur</td><td>28–32&nbsp;°C</td><td>Flächige T5-HO Tageslichtröhren (6500&nbsp;K)</td></tr>
        <tr><td>Kühle Zone</td><td>24–26&nbsp;°C</td><td>Schattenbereiche, Höhlen</td></tr>
        <tr><td>Nacht</td><td>18–22&nbsp;°C</td><td>keine Zusatzheizung, Raumtemperatur genügt</td></tr>
    </tbody>
</table>
<p><strong>UV-B:</strong> Hochwertige Mischlichtlampe mit Reflektor (z.&nbsp;B. SolarRaptor). Brenndauer 11–12&nbsp;Stunden, Tauschintervalle gemäß Hersteller.</p>

<h2>Fütterung</h2>
<p>Bartagamen sind omnivor. Der Insektenanteil nimmt mit dem Alter ab:</p>
<ul>
    <li><strong>Jungtiere:</strong> 70&nbsp;% Insekten, 30&nbsp;% Grünfutter, tägliche Fütterung.</li>
    <li><strong>Subadulte:</strong> 50&nbsp;% Insekten, Rest Wildkräuter, drei bis vier Fütterungen pro Woche.</li>
    <li><strong>Adulte:</strong> 20–30&nbsp;% Insekten, Schwerpunkt auf Wildkräuter- und Blütenmix.</li>
</ul>
<p>Calcium ohne D3 bei jeder Insektenfütterung, Kombipräparat zweimal wöchentlich. Insekten stets gut füttern (<em>gut loaden</em>).</p>

<h2>Gesundheitsvorsorge</h2>
<p>Mindestens jährlich Kotuntersuchung auf Parasiten, Gewichtsprotokoll führen und auf Hinweise einer metabolischen Knochenerkrankung achten (Schwellungen am Kiefer, weicher Panzer). Bei Auffälligkeiten sofort reptilienkundigen Tierarzt konsultieren.</p>

<h2>Winterruhe und Zucht</h2>
<p>Eine Winterruhe von 8–10 Wochen bei 16&nbsp;°C stabilisiert den Stoffwechsel und ist Voraussetzung für die Zucht. Nach dem Erwärmen Paarungen nur mit gesunden, ausgewachsenen Tieren ansetzen.</p>
HTML,
        ],
        'heterodon-nasicus' => [
            'title' => 'Heterodon nasicus – Pflegeleitfaden für Hakennasennattern',
            'summary' => 'Ausführlicher Leitfaden zur Haltung, Ernährung, Winterruhe und Genetikplanung der westlichen Hakennasennatter (Heterodon nasicus).',
            'content' => <<<HTML
<h2>Steckbrief</h2>
<p><strong>Wissenschaftlicher Name:</strong> <em>Heterodon nasicus</em><br>
<strong>Verbreitung:</strong> Zentrale Prärien Nordamerikas<br>
<strong>Biotop:</strong> Sandige Steppen, lückige Grasflächen<br>
<strong>Charakter:</strong> tagaktiv, grabend, stressresistent</p>

<h2>Unterbringung</h2>
<p>Ein Einzeltier benötigt mindestens <strong>90 × 45 × 45&nbsp;cm</strong>. Für Paare bzw. größere Tiere sind 120&nbsp;cm Länge ideal.</p>
<ul>
    <li><strong>Substrat:</strong> Gemisch aus Sand, Pinienhumus und Sphagnum, 8–10&nbsp;cm tief zum Eingraben.</li>
    <li><strong>Verstecke:</strong> Korkröhren, Halbhöhlen und grabfähige Boxen.</li>
    <li><strong>Feuchtebox:</strong> Plastikbox mit feuchtem Sphagnum zur Häutung.</li>
</ul>

<h3>Klima</h3>
<table>
    <thead><tr><th>Zone</th><th>Temperatur</th><th>Hinweis</th></tr></thead>
    <tbody>
        <tr><td>Sonnenplatz</td><td>33–35&nbsp;°C</td><td>Spotstrahler 35–50&nbsp;W</td></tr>
        <tr><td>Grundtemperatur</td><td>26–28&nbsp;°C</td><td>Flächenheizung oder Keramikstrahler</td></tr>
        <tr><td>Nacht</td><td>20–22&nbsp;°C</td><td>leichter Temperaturabfall fördert Aktivität</td></tr>
    </tbody>
</table>
<p>Luftfeuchte tagsüber 40–50&nbsp;%, nachts 60&nbsp;%. Kurzes Sprühen genügt.</p>

<h2>Ernährung</h2>
<p>Hakennasennattern fressen in Gefangenschaft zuverlässig frostaufgetaute Nager in passenden Größen. Ergänzend sind Geruchsreize durch Duftstreifen (z.&nbsp;B. Amphibien) möglich. Nach der Fütterung drei bis vier Tage Ruhe gewähren.</p>

<h2>Winterruhe</h2>
<p>Von November bis Januar empfiehlt sich eine Winterruhe bei 10–12&nbsp;°C. Drei Wochen vorher keine Fütterung mehr, Tiere entleeren lassen, dann langsam abkühlen. Nach dem Aufwärmen steigert man das Futterangebot, bevor man Zuchtprojekte startet.</p>

<h2>Genetik und Morphe</h2>
<p>Die Art weist zahlreiche rezessive und inkomplett dominante Linien auf (Albino, Toffee, Axanthic, Anaconda, Arctic, Hypo). Unser Genetik-Rechner unterstützt die Planung: Markieren Sie visuelle Formen (z.&nbsp;B. „Albino“) sowie Trägerstatus („het Toffee“), um Punnett-Auswertungen für Nachzuchten zu erhalten.</p>

<h2>Gesundheitsmanagement</h2>
<ul>
    <li>Regelmäßige Gewichtskontrollen – Abnahmen deuten auf Parasiten oder zu niedrige Temperaturen hin.</li>
    <li>Achten Sie auf Resthäutungen an der Schwanzspitze, bei Bedarf lauwarmes Bad anbieten.</li>
    <li>Frisches Wasser täglich, Näpfe mindestens wöchentlich desinfizieren.</li>
</ul>
HTML,
        ],
        'heloderma-suspectum' => [
            'title' => 'Heloderma suspectum – Leitfaden Gila-Krustenechse',
            'summary' => 'Pflegehinweise für Heloderma suspectum mit Fokus auf Sicherheit, Ernährung und klimatische Ansprüche der giftigen Krustenechsen.',
            'content' => <<<HTML
<h2>Rechtlicher Hinweis</h2>
<p><strong>Heloderma</strong>-Arten sind giftig und vielerorts genehmigungspflichtig. Prüfen Sie vor der Anschaffung die lokalen Auflagen (Sachkunde, Anzeige- bzw. Haltegenehmigung).</p>

<h2>Habitat und Gehege</h2>
<p>Empfohlen wird ein Bodenterrarium von mindestens <strong>200 × 100 × 80&nbsp;cm</strong> mit massiver Sicherung. Doppelte Türen oder Vorraum sind Pflicht.</p>
<ul>
    <li><strong>Substrat:</strong> Lehm-Sand-Gemisch, stellenweise mit Kies und großflächigen Steinplatten.</li>
    <li><strong>Struktur:</strong> schwere Wurzelstücke, Höhlen, trockene und feuchte Rückzugsbereiche.</li>
    <li><strong>Sicherheit:</strong> Terrarium verriegeln, Arbeitshaken und Schutzbox bereithalten.</li>
</ul>

<h3>Klima</h3>
<table>
    <thead><tr><th>Bereich</th><th>Temperatur</th><th>Feuchte</th></tr></thead>
    <tbody>
        <tr><td>Hot Spot</td><td>38–40&nbsp;°C</td><td>trocken</td></tr>
        <tr><td>Grundbereich</td><td>28–30&nbsp;°C</td><td>40–50&nbsp;%</td></tr>
        <tr><td>Feuchtversteck</td><td>24–26&nbsp;°C</td><td>70&nbsp;%</td></tr>
        <tr><td>Nacht</td><td>20–22&nbsp;°C</td><td>leicht erhöht</td></tr>
    </tbody>
</table>

<h2>Ernährung</h2>
<p>Gila-Krustenechsen sind carnivor. Füttern Sie adulte Tiere alle 10–14 Tage mit kleinen Nagern, Küken oder Wachteleiern. Jungtiere erhalten wöchentlich kleinere Portionen. Überfütterung führt zu Fettleibigkeit; ein Jahreszyklus mit reduzierter Winterfütterung ist empfehlenswert.</p>

<h2>Handhabung</h2>
<p>Handling nur mit Haken und Sicherungsbehälter. Bisse sind schmerzhaft und medizinisch ernstzunehmen. Arbeiten Sie stets zu zweit.</p>
HTML,
        ],
        'heloderma-horridum' => [
            'title' => 'Heloderma horridum – Leitfaden Mexikanische Krustenechse',
            'summary' => 'Spezialisierter Leitfaden für Heloderma horridum mit Schwerpunkt auf Mikroklima und saisonaler Pflege.',
            'content' => <<<HTML
<h2>Besonderheiten</h2>
<p><em>Heloderma horridum</em> lebt in semi-ariden Regionen Mexikos und benötigt saisonale Schwankungen. Eine Regenzeit-Phase fördert Wohlbefinden und Fortpflanzung.</p>

<h2>Klima und Saison</h2>
<ul>
    <li><strong>Trockenzeit (Oktober–März):</strong> Tag 26–28&nbsp;°C, Nacht 18&nbsp;°C, Luftfeuchte 35–40&nbsp;%, reduziertes Futter.</li>
    <li><strong>Regenzeit (April–September):</strong> Tag 30–32&nbsp;°C, Nacht 22&nbsp;°C, Luftfeuchte 60–70&nbsp;%, regelmäßige Sprühintervalle.</li>
</ul>

<h2>Ernährung</h2>
<p>Das Nahrungsspektrum umfasst Nager, Eizellen, Nestlinge und gelegentlich Insekten. Kalkulieren Sie die Jahresration: Erwachsene benötigen rund 15&nbsp;% ihres Körpergewichts an Futter pro Jahr.</p>

<h2>Zucht</h2>
<p>Nach der Regenzeit lassen sich Pärchen zusammenführen. Eiablage erfolgt 45–55 Tage nach der Paarung in lockerer Erde. Inkubation bei 29&nbsp;°C und 70&nbsp;% Feuchte dauert 150–180 Tage.</p>
HTML,
        ],
        'heloderma-alvarezi' => [
            'title' => 'Heloderma alvarezi – Leitfaden Chiapan-Krustenechse',
            'summary' => 'Pflegeprofil für Heloderma alvarezi mit Fokus auf hohe Luftfeuchte und strukturreiche Terrarien.',
            'content' => <<<HTML
<h2>Habitat</h2>
<p><em>Heloderma alvarezi</em> stammt aus den feuchten Bergregionen Chiapas. Terrarien sollten dicht bepflanzte Bereiche, Kletterstrukturen und mehrere feuchte Rückzugsorte enthalten.</p>

<h2>Klima</h2>
<table>
    <thead><tr><th>Tag</th><th>Nacht</th><th>Feuchte</th></tr></thead>
    <tbody>
        <tr><td>27–29&nbsp;°C</td><td>20–22&nbsp;°C</td><td>65–75&nbsp;%</td></tr>
    </tbody>
</table>
<p>Nebel- oder Beregnungsanlagen mit Intervallen von 2–3 Minuten morgens und abends stabilisieren die Luftfeuchte.</p>

<h2>Pflegehinweise</h2>
<ul>
    <li>Große Wasserschale zum Baden bereitstellen, wöchentlich wechseln.</li>
    <li>Proteinreiche Ernährung (Nager, Wachteleier), Ergänzung durch Calciumpräparate.</li>
    <li>Regelmäßige Gesundheitschecks inkl. Blutprofil empfehlenswert.</li>
</ul>
HTML,
        ],
    ];

    foreach ($defaults as $slug => $data) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM care_articles WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        if ($stmt->fetchColumn() > 0) {
            continue;
        }
        create_care_article($pdo, [
            'title' => $data['title'],
            'slug' => $slug,
            'summary' => $data['summary'],
            'content' => $data['content'],
            'is_published' => 1,
        ]);
    }
}

