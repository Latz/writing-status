#!/bin/bash

set -euo pipefail

# ── Box-Zeichnung ────────────────────────────────────────────────────────────
BW=62  # innere Breite
_box_top() { printf '╔%s╗\n' "$(printf '═%.0s' $(seq 1 $BW))"; }
_box_sep() { printf '╠%s╣\n' "$(printf '═%.0s' $(seq 1 $BW))"; }
_box_bot() { printf '╚%s╝\n' "$(printf '═%.0s' $(seq 1 $BW))"; }
_box_row() { printf "║%-${BW}s║\n" "$1"; }
_box_title() {
  local t="$1" pad=$(( (BW - ${#1}) / 2 ))
  _box_row "$(printf "%${pad}s%s" "" "$t")"
}
_box_col() {  # _box_col "Label" "Val1" "Val2" "Val3"
  local row
  row=$(printf "  %-20s %7s    %9s    %7s" "$1" "$2" "$3" "$4")
  _box_row "$row"
}
# ────────────────────────────────────────────────────────────────────────────

# -y / --yes : alle Pausen überspringen
NON_INTERACTIVE=false
for ARG in "$@"; do
  [[ "$ARG" == "-y" || "$ARG" == "--yes" ]] && NON_INTERACTIVE=true
done

weiter_oder_beenden() {
  if $NON_INTERACTIVE; then return; fi
  read -rp "      [Enter] weiter  [q] beenden: " INPUT
  if [[ "${INPUT,,}" == "q" ]]; then
    echo ""
    echo "      Abgebrochen."
    exit 0
  fi
}

echo ""
echo "================================================"
echo " SonarCloud Analyse – $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================================"
echo ""

# 1. Ordner für Berichte erstellen, falls er nicht existiert
echo "[1/5] Vorbereitung: Erstelle reports/-Verzeichnis..."
mkdir -p reports
echo "      OK"
echo ""

# 2. Quelldateien in Temp-Verzeichnis kopieren, PHPCBF darauf anwenden
echo "[2/7] Auto-Fix mit PHPCBF (Temp-Kopie, Quelldateien bleiben unberührt)..."
SCAN_TMP=$(mktemp -d /tmp/wpcs-scan-XXXXXX)
cp -r writing-status.php class-writing-status-renderer.php includes/ "$SCAN_TMP/"
echo "      Temp-Verzeichnis : $SCAN_TMP"
./vendor/bin/phpcbf --standard=WordPress "$SCAN_TMP/" || true
echo "      OK"
echo ""

# 3. WordPress PHP_CodeSniffer auf die gefixte Kopie ausführen
echo "[3/7] WordPress-Code-Analyse (PHPCS)..."
./vendor/bin/phpcs --standard=WordPress "$SCAN_TMP/" \
  --report=json --report-file=reports/wpcs-report.json || true
rm -rf "$SCAN_TMP"

ISSUE_COUNT=$(jq '.totals.errors + .totals.warnings' reports/wpcs-report.json 2>/dev/null || echo "?")
ERRORS=$(jq '.totals.errors' reports/wpcs-report.json 2>/dev/null || echo "?")
WARNINGS=$(jq '.totals.warnings' reports/wpcs-report.json 2>/dev/null || echo "?")
FIXABLE=$(jq '.totals.fixable' reports/wpcs-report.json 2>/dev/null || echo "?")

echo ""
_box_top
_box_title "PHPCS Zusammenfassung"
_box_sep
_box_row "  Fehler    : $ERRORS"
_box_row "  Warnungen : $WARNINGS"
_box_row "  Gesamt    : $ISSUE_COUNT"
_box_row "  Auto-fix  : $FIXABLE"
_box_sep
_box_row "  Anzahl  Regel"
_box_sep
jq -r '[.files[].messages[].source] | group_by(.) | map({rule: .[0], count: length}) | sort_by(-.count)[] | "\(.count)\t\(.rule)"' \
  reports/wpcs-report.json 2>/dev/null \
  | while IFS=$'\t' read -r COUNT RULE; do
    _box_row "$(printf "  %5s  %s" "$COUNT" "$RULE")"
  done || true
_box_bot
echo ""
weiter_oder_beenden
echo ""

# 4. PHPStan ausführen
echo "[4/7] Statische Analyse (PHPStan)..."
echo "      Level  : max"
echo "      Ausgabe: reports/phpstan-report.json"
~/.config/composer/vendor/bin/phpstan analyse --error-format=json \
  > reports/phpstan-report.json || true
PHPSTAN_COUNT=$(jq '.totals.file_errors + .totals.errors' reports/phpstan-report.json 2>/dev/null || echo "?")
PHPSTAN_GLOBAL=$(jq '.totals.errors' reports/phpstan-report.json 2>/dev/null || echo "?")
PHPSTAN_FILE=$(jq '.totals.file_errors' reports/phpstan-report.json 2>/dev/null || echo "?")

echo ""
_box_top
_box_title "PHPStan Zusammenfassung"
_box_sep
_box_row "  Fehler gesamt  : $PHPSTAN_COUNT"
_box_row "  Datei-Fehler   : $PHPSTAN_FILE"
_box_row "  Globale Fehler : $PHPSTAN_GLOBAL"
_box_sep
_box_row "  Anzahl  Datei"
_box_sep
jq -r '.files | to_entries[] | select(.value.errors > 0) | "\(.value.errors)\t\(.key | split("/")[-1])"' \
  reports/phpstan-report.json 2>/dev/null \
  | while IFS=$'\t' read -r COUNT FILE; do
    _box_row "$(printf "  %5s  %s" "$COUNT" "$FILE")"
  done || true
_box_sep
_box_row "  Anzahl  Identifier"
_box_sep
jq -r '[.files[].messages[].identifier] | group_by(.) | map({id: .[0], count: length}) | sort_by(-.count)[] | "\(.count)\t\(.id)"' \
  reports/phpstan-report.json 2>/dev/null \
  | while IFS=$'\t' read -r COUNT ID; do
    _box_row "$(printf "  %5s  %s" "$COUNT" "$ID")"
  done || true
_box_bot
echo ""
weiter_oder_beenden
echo ""

# 5. ESLint ausführen
echo "[5/8] JavaScript-Analyse (ESLint)..."
echo "      Standard : @wordpress/eslint-plugin/recommended"
echo "      Ausgabe  : reports/eslint-report.json"
npx eslint --format json writing-status.js > reports/eslint-report.json || true

ESLINT_ERRORS=$(jq '[.[].messages[] | select(.severity == 2)] | length' reports/eslint-report.json 2>/dev/null || echo "?")
ESLINT_WARNINGS=$(jq '[.[].messages[] | select(.severity == 1)] | length' reports/eslint-report.json 2>/dev/null || echo "?")
ESLINT_TOTAL=$(jq '[.[].messages | length] | add // 0' reports/eslint-report.json 2>/dev/null || echo "?")

echo ""
_box_top
_box_title "ESLint Zusammenfassung"
_box_sep
_box_row "  Fehler    : $ESLINT_ERRORS"
_box_row "  Warnungen : $ESLINT_WARNINGS"
_box_row "  Gesamt    : $ESLINT_TOTAL"
_box_sep
_box_row "  Anzahl  Regel"
_box_sep
jq -r '[.[].messages[].ruleId] | group_by(.) | map({rule: .[0], count: length}) | sort_by(-.count)[] | "\(.count)\t\(.rule)"' \
  reports/eslint-report.json 2>/dev/null \
  | while IFS=$'\t' read -r COUNT RULE; do
    _box_row "$(printf "  %5s  %s" "$COUNT" "$RULE")"
  done || true
_box_bot
echo ""
weiter_oder_beenden
echo ""

# 6. WordPress Plugin Check
echo "[6/9] WordPress Plugin Check..."
echo "      Plugin : draft-status"
echo "      Ausgabe: reports/plugin-check-report.txt"
wp plugin check draft-status --path=/home/latz/www/wp > reports/plugin-check-report.txt 2>/dev/null || true

PC_ERRORS=$(awk 'NF && !/^FILE/ && !/^line/ {print $3}' reports/plugin-check-report.txt | grep -c "^ERROR$" || true)
PC_WARNINGS=$(awk 'NF && !/^FILE/ && !/^line/ {print $3}' reports/plugin-check-report.txt | grep -c "^WARNING$" || true)
PC_TOTAL=$(( PC_ERRORS + PC_WARNINGS ))

echo ""
_box_top
_box_title "WP Plugin Check Zusammenfassung"
_box_sep
_box_row "  Fehler    : $PC_ERRORS"
_box_row "  Warnungen : $PC_WARNINGS"
_box_row "  Gesamt    : $PC_TOTAL"
_box_sep
_box_row "  Anzahl  Code"
_box_sep
awk 'NF && !/^FILE/ && !/^line/ {print $4}' reports/plugin-check-report.txt \
  | sort | uniq -c | sort -rn \
  | while read -r COUNT CODE; do
    _box_row "$(printf "  %5s  %s" "$COUNT" "$CODE")"
  done || true
_box_bot
echo ""
weiter_oder_beenden
echo ""

# 7. Semgrep Scan
echo "[7/10] Semgrep Scan..."
echo "       Regeln : p/php + wordpress-semgrep-rules/configs/plugin-development.yaml"
echo "       Ausgabe: reports/semgrep-report.json"
semgrep scan \
  --config="p/php" \
  --config="/home/latz/tools/wordpress-semgrep-rules/configs/plugin-development.yaml" \
  --json \
  --output=reports/semgrep-report.json \
  writing-status.php class-writing-status-renderer.php includes/ || true

SG_ERRORS=$(jq '[.results[] | select(.extra.severity == "ERROR")] | length' reports/semgrep-report.json 2>/dev/null || echo "?")
SG_WARNINGS=$(jq '[.results[] | select(.extra.severity == "WARNING")] | length' reports/semgrep-report.json 2>/dev/null || echo "?")
SG_INFO=$(jq '[.results[] | select(.extra.severity == "INFO")] | length' reports/semgrep-report.json 2>/dev/null || echo "?")
SG_TOTAL=$(jq '.results | length' reports/semgrep-report.json 2>/dev/null || echo "?")

echo ""
_box_top
_box_title "Semgrep Zusammenfassung"
_box_sep
_box_row "  Fehler    : $SG_ERRORS"
_box_row "  Warnungen : $SG_WARNINGS"
_box_row "  Info      : $SG_INFO"
_box_row "  Gesamt    : $SG_TOTAL"
_box_sep
_box_row "  Anzahl  Regel"
_box_sep
jq -r '[.results[].check_id] | group_by(.) | map({rule: .[0], count: length}) | sort_by(-.count)[] | "\(.count)\t\(.rule)"' \
  reports/semgrep-report.json 2>/dev/null \
  | while IFS=$'\t' read -r COUNT RULE; do
    _box_row "$(printf "  %5s  %s" "$COUNT" "$RULE")"
  done || true
_box_bot
echo ""
weiter_oder_beenden
echo ""

# 8. Sonar-Scanner ausführen (inklusive WordPress-Report-Import)
echo "[8/10] Sende Daten an SonarCloud..."
echo "      Konfiguration : sonar-project.properties"
echo "      Token         : aus .env"
if [ -f .env ]; then
  export SONAR_TOKEN=$(grep -E '^SONAR_TOKEN=' .env | cut -d= -f2 | tr -d '\r\n')
fi
/opt/sonar-scanner/bin/sonar-scanner \
  -Dsonar.php.codesniffer.reportPaths=reports/wpcs-report.json
echo "      Upload abgeschlossen"
echo ""

# 7. Aktuelle offene Issues via API abrufen
echo "[9/10] Rufe offene Issues von SonarCloud-API ab..."
curl -s -u "${SONAR_TOKEN}": \
  "https://sonarcloud.io/api/issues/search?componentKeys=Latz_draft-status&resolved=false" \
  > sonar-issues.json
OPEN_COUNT=$(jq '.total' sonar-issues.json 2>/dev/null || echo "?")
echo "      Offene Issues: $OPEN_COUNT"
echo ""

# 8. Markdown-Bericht generieren + PHPCS-Regelbeschreibungen anhängen
echo "[10/10] Generiere Markdown-Bericht (sonar-issues.md)..."

# SonarCloud Issues als Tabelle
jq -r '
  "## SonarCloud Issues\n",
  "| Severity | File | Line | Rule | Message |",
  "|----------|------|------|------|---------|",
  (.issues[] | "| \(.severity) | \(.component | split(":")[1]) | \(.line // "") | \(.rule) | \(.message | gsub("\\|"; "\\|")) |")
' sonar-issues.json > sonar-issues.md

echo "      OK"
echo ""

_n() { [[ "$1" =~ ^[0-9]+$ ]] && echo "$1" || echo "0"; }
GESAMT_TOTAL=$(( $(_n "$ISSUE_COUNT") + $(_n "$PHPSTAN_COUNT") + $(_n "$ESLINT_TOTAL") + $(_n "$PC_TOTAL") + $(_n "$SG_TOTAL") ))

echo ""
_box_top
_box_title "ANALYSE ABGESCHLOSSEN"
_box_sep
_box_row "  $(date '+%Y-%m-%d %H:%M:%S')"
_box_sep
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "Tool" "Fehler" "Warnungen" "Gesamt")"
_box_sep
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "PHPCS"          "$(jq '.totals.errors'   reports/wpcs-report.json    2>/dev/null||echo '?')" "$(jq '.totals.warnings' reports/wpcs-report.json 2>/dev/null||echo '?')" "$ISSUE_COUNT")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "PHPStan"        "$PHPSTAN_FILE"   "$PHPSTAN_GLOBAL"  "$PHPSTAN_COUNT")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "ESLint"         "$ESLINT_ERRORS"  "$ESLINT_WARNINGS" "$ESLINT_TOTAL")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "WP Plugin Check" "$PC_ERRORS"     "$PC_WARNINGS"     "$PC_TOTAL")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "Semgrep"        "$SG_ERRORS"      "$SG_WARNINGS"     "$SG_TOTAL")"
_box_sep
_box_row "$(printf "  %-20s  %28s" "Gesamt" "$GESAMT_TOTAL Probleme")"
_box_sep
_box_row "$(printf "  %-20s  %28s" "SonarCloud Issues" "$OPEN_COUNT offen")"
_box_row "$(printf "  %-20s  %28s" "Bericht" "sonar-issues.md")"
_box_bot
echo ""
