#!/bin/bash
set -euo pipefail

# ── Konfiguration ─────────────────────────────────────────────────────────────
WP_PATH="/home/latz/www/wp"
WP_PLUGIN="draft-status"
SONARCLOUD_PROJECT="Latz_draft-status"
SEMGREP_RULES="/home/latz/tools/wordpress-semgrep-rules/configs/plugin-development.yaml"
PHP_SOURCES="writing-status.php class-writing-status-renderer.php includes/"
JS_SOURCES="writing-status.js"

SONAR_TOKEN="${SONAR_TOKEN:-}"
if [ -f .env ]; then
    SONAR_TOKEN=$(grep -E '^SONAR_TOKEN=' .env | cut -d= -f2 | tr -d '\r\n')
fi

# ── Optionen ──────────────────────────────────────────────────────────────────
NON_INTERACTIVE=false
for ARG in "$@"; do
    [[ "$ARG" == "-y" || "$ARG" == "--yes" ]] && NON_INTERACTIVE=true
done

# ── Temp-Verzeichnis mit automatischem Cleanup ────────────────────────────────
SCAN_TMP=""
cleanup() { [ -n "$SCAN_TMP" ] && rm -rf "$SCAN_TMP"; }
trap cleanup EXIT

# ── Hilfsfunktionen ───────────────────────────────────────────────────────────
BW=62

_box_top()   { printf '╔%s╗\n' "$(printf '═%.0s' $(seq 1 $BW))"; }
_box_sep()   { printf '╠%s╣\n' "$(printf '═%.0s' $(seq 1 $BW))"; }
_box_bot()   { printf '╚%s╝\n' "$(printf '═%.0s' $(seq 1 $BW))"; }
_box_row()   { printf "║%-${BW}s║\n" "$1"; }
_box_title() { local pad=$(( (BW - ${#1}) / 2 )); _box_row "$(printf "%${pad}s%s" "" "$1")"; }
_box_rule()  { _box_row "$(printf "  %5s  %s" "$1" "$2")"; }

_n() { [[ "$1" =~ ^[0-9]+$ ]] && echo "$1" || echo "0"; }

pause() {
    if $NON_INTERACTIVE; then return; fi
    read -rp "  [Enter] weiter  [q] beenden: " _INPUT
    if [[ "${_INPUT,,}" == "q" ]]; then
        echo "  Abgebrochen."; exit 0
    fi
}

step() { echo; echo "[$1/10] $2"; }

# ── Start ─────────────────────────────────────────────────────────────────────
echo
_box_top
_box_title "Statische Code-Analyse"
_box_sep
_box_row "  $(date '+%Y-%m-%d %H:%M:%S')"
_box_row "  Projekt : $WP_PLUGIN"
_box_bot
echo

# ── 1. Vorbereitung ───────────────────────────────────────────────────────────
step 1 "Vorbereitung"
mkdir -p reports
echo "  reports/ bereit"

# ── 2. PHPCBF (Temp-Kopie, Quelldateien bleiben unberührt) ───────────────────
step 2 "PHPCBF – Auto-Fix auf Temp-Kopie"
SCAN_TMP=$(mktemp -d /tmp/wpcs-scan-XXXXXX)
# shellcheck disable=SC2086
cp -r $PHP_SOURCES "$SCAN_TMP/"
./vendor/bin/phpcbf --standard=WordPress "$SCAN_TMP/" || true

# ── 3. PHPCS ──────────────────────────────────────────────────────────────────
step 3 "PHPCS – WordPress Coding Standards"
./vendor/bin/phpcs --standard=WordPress "$SCAN_TMP/" \
    --report=json --report-file=reports/wpcs-report.json || true

PHPCS_ERRORS=$(jq '.totals.errors'                      reports/wpcs-report.json 2>/dev/null || echo "?")
PHPCS_WARN=$(jq   '.totals.warnings'                    reports/wpcs-report.json 2>/dev/null || echo "?")
PHPCS_TOTAL=$(jq  '.totals.errors + .totals.warnings'   reports/wpcs-report.json 2>/dev/null || echo "?")
PHPCS_FIX=$(jq    '.totals.fixable'                     reports/wpcs-report.json 2>/dev/null || echo "?")

echo
_box_top
_box_title "PHPCS Zusammenfassung"
_box_sep
_box_row "  Fehler    : $PHPCS_ERRORS"
_box_row "  Warnungen : $PHPCS_WARN"
_box_row "  Gesamt    : $PHPCS_TOTAL"
_box_row "  Auto-fix  : $PHPCS_FIX"
_box_sep
_box_row "  Anzahl  Regel"
_box_sep
jq -r '[.files[].messages[].source]
    | group_by(.) | map({rule: .[0], count: length})
    | sort_by(-.count)[]
    | "\(.count)\t\(.rule)"' reports/wpcs-report.json 2>/dev/null \
    | while IFS=$'\t' read -r C R; do _box_rule "$C" "$R"; done || true
_box_bot
echo
pause

# ── 4. PHPStan ────────────────────────────────────────────────────────────────
step 4 "PHPStan – Statische Analyse"
~/.config/composer/vendor/bin/phpstan analyse --error-format=json \
    > reports/phpstan-report.json || true

PHPSTAN_FILES=$(jq  '.totals.file_errors'                       reports/phpstan-report.json 2>/dev/null || echo "?")
PHPSTAN_GLOBAL=$(jq '.totals.errors'                            reports/phpstan-report.json 2>/dev/null || echo "?")
PHPSTAN_TOTAL=$(jq  '.totals.file_errors + .totals.errors'      reports/phpstan-report.json 2>/dev/null || echo "?")

echo
_box_top
_box_title "PHPStan Zusammenfassung"
_box_sep
_box_row "  Datei-Fehler   : $PHPSTAN_FILES"
_box_row "  Globale Fehler : $PHPSTAN_GLOBAL"
_box_row "  Gesamt         : $PHPSTAN_TOTAL"
_box_sep
_box_row "  Anzahl  Datei"
_box_sep
jq -r '.files | to_entries[]
    | select(.value.errors > 0)
    | "\(.value.errors)\t\(.key | split("/")[-1])"' reports/phpstan-report.json 2>/dev/null \
    | while IFS=$'\t' read -r C F; do _box_rule "$C" "$F"; done || true
_box_sep
_box_row "  Anzahl  Identifier"
_box_sep
jq -r '[.files[].messages[].identifier]
    | group_by(.) | map({id: .[0], count: length})
    | sort_by(-.count)[]
    | "\(.count)\t\(.id)"' reports/phpstan-report.json 2>/dev/null \
    | while IFS=$'\t' read -r C I; do _box_rule "$C" "$I"; done || true
_box_bot
echo
pause

# ── 5. ESLint ─────────────────────────────────────────────────────────────────
step 5 "ESLint – JavaScript (@wordpress/eslint-plugin)"
# shellcheck disable=SC2086
npx eslint --format json $JS_SOURCES > reports/eslint-report.json || true

ESLINT_ERRORS=$(jq '[.[].messages[] | select(.severity == 2)] | length'  reports/eslint-report.json 2>/dev/null || echo "?")
ESLINT_WARN=$(jq   '[.[].messages[] | select(.severity == 1)] | length'  reports/eslint-report.json 2>/dev/null || echo "?")
ESLINT_TOTAL=$(jq  '[.[].messages | length] | add // 0'                  reports/eslint-report.json 2>/dev/null || echo "?")

echo
_box_top
_box_title "ESLint Zusammenfassung"
_box_sep
_box_row "  Fehler    : $ESLINT_ERRORS"
_box_row "  Warnungen : $ESLINT_WARN"
_box_row "  Gesamt    : $ESLINT_TOTAL"
_box_sep
_box_row "  Anzahl  Regel"
_box_sep
jq -r '[.[].messages[].ruleId]
    | group_by(.) | map({rule: .[0], count: length})
    | sort_by(-.count)[]
    | "\(.count)\t\(.rule)"' reports/eslint-report.json 2>/dev/null \
    | while IFS=$'\t' read -r C R; do _box_rule "$C" "$R"; done || true
_box_bot
echo
pause

# ── 6. WP Plugin Check ────────────────────────────────────────────────────────
step 6 "WP Plugin Check"
wp plugin check "$WP_PLUGIN" --path="$WP_PATH" \
    > reports/plugin-check-report.txt 2>/dev/null || true

PC_ERRORS=$(awk 'NF && !/^FILE/ && !/^line/ {print $3}' reports/plugin-check-report.txt \
    | grep -c "^ERROR$"   || true)
PC_WARN=$(awk   'NF && !/^FILE/ && !/^line/ {print $3}' reports/plugin-check-report.txt \
    | grep -c "^WARNING$" || true)
PC_TOTAL=$(( PC_ERRORS + PC_WARN ))

echo
_box_top
_box_title "WP Plugin Check Zusammenfassung"
_box_sep
_box_row "  Fehler    : $PC_ERRORS"
_box_row "  Warnungen : $PC_WARN"
_box_row "  Gesamt    : $PC_TOTAL"
_box_sep
_box_row "  Anzahl  Code"
_box_sep
awk 'NF && !/^FILE/ && !/^line/ {print $4}' reports/plugin-check-report.txt \
    | sort | uniq -c | sort -rn \
    | while read -r C CODE; do _box_rule "$C" "$CODE"; done || true
_box_bot
echo
pause

# ── 7. Semgrep ────────────────────────────────────────────────────────────────
step 7 "Semgrep – p/php + WordPress-Regeln"
# shellcheck disable=SC2086
semgrep scan \
    --config="p/php" \
    --config="$SEMGREP_RULES" \
    --json \
    --output=reports/semgrep-report.json \
    $PHP_SOURCES || true

SG_ERRORS=$(jq '[.results[] | select(.extra.severity == "ERROR")]   | length' reports/semgrep-report.json 2>/dev/null || echo "?")
SG_WARN=$(jq   '[.results[] | select(.extra.severity == "WARNING")] | length' reports/semgrep-report.json 2>/dev/null || echo "?")
SG_INFO=$(jq   '[.results[] | select(.extra.severity == "INFO")]    | length' reports/semgrep-report.json 2>/dev/null || echo "?")
SG_TOTAL=$(jq  '.results | length'                                             reports/semgrep-report.json 2>/dev/null || echo "?")

echo
_box_top
_box_title "Semgrep Zusammenfassung"
_box_sep
_box_row "  Fehler    : $SG_ERRORS"
_box_row "  Warnungen : $SG_WARN"
_box_row "  Info      : $SG_INFO"
_box_row "  Gesamt    : $SG_TOTAL"
_box_sep
_box_row "  Anzahl  Regel"
_box_sep
jq -r '[.results[].check_id]
    | group_by(.) | map({rule: .[0], count: length})
    | sort_by(-.count)[]
    | "\(.count)\t\(.rule)"' reports/semgrep-report.json 2>/dev/null \
    | while IFS=$'\t' read -r C R; do _box_rule "$C" "$R"; done || true
_box_bot
echo
pause

# ── 8. SonarCloud Upload ──────────────────────────────────────────────────────
step 8 "SonarCloud Upload"
echo "  Konfiguration : sonar-project.properties"
/opt/sonar-scanner/bin/sonar-scanner \
    -Dsonar.php.codesniffer.reportPaths=reports/wpcs-report.json
echo "  Upload abgeschlossen"

# ── 9. Offene Issues abrufen ──────────────────────────────────────────────────
step 9 "SonarCloud Issues abrufen"
curl -s -u "${SONAR_TOKEN}:" \
    "https://sonarcloud.io/api/issues/search?componentKeys=${SONARCLOUD_PROJECT}&resolved=false" \
    > sonar-issues.json
OPEN_COUNT=$(jq '.total' sonar-issues.json 2>/dev/null || echo "?")
echo "  Offene Issues: $OPEN_COUNT"

# ── 10. Markdown-Bericht ──────────────────────────────────────────────────────
step 10 "Markdown-Bericht generieren"
jq -r '
    "## SonarCloud Issues\n",
    "| Severity | File | Line | Rule | Message |",
    "|----------|------|------|------|---------|",
    (.issues[] | "| \(.severity) | \(.component | split(":")[1]) | \(.line // "") | \(.rule) | \(.message | gsub("\\|"; "\\|")) |")
' sonar-issues.json > sonar-issues.md
echo "  sonar-issues.md erstellt"

# ── Zusammenfassung ───────────────────────────────────────────────────────────
GESAMT=$(( $(_n "$PHPCS_TOTAL") + $(_n "$PHPSTAN_TOTAL") + $(_n "$ESLINT_TOTAL") + $(_n "$PC_TOTAL") + $(_n "$SG_TOTAL") ))

echo
_box_top
_box_title "ANALYSE ABGESCHLOSSEN"
_box_sep
_box_row "  $(date '+%Y-%m-%d %H:%M:%S')"
_box_sep
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "Tool" "Fehler" "Warnungen" "Gesamt")"
_box_sep
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "PHPCS"           "$PHPCS_ERRORS"   "$PHPCS_WARN"    "$PHPCS_TOTAL")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "PHPStan"         "$PHPSTAN_FILES"  "$PHPSTAN_GLOBAL" "$PHPSTAN_TOTAL")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "ESLint"          "$ESLINT_ERRORS"  "$ESLINT_WARN"   "$ESLINT_TOTAL")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "WP Plugin Check" "$PC_ERRORS"      "$PC_WARN"       "$PC_TOTAL")"
_box_row "$(printf "  %-20s  %7s  %10s  %8s" "Semgrep"         "$SG_ERRORS"      "$SG_WARN"       "$SG_TOTAL")"
_box_sep
_box_row "$(printf "  %-20s  %28s" "Gesamt" "$GESAMT Probleme")"
_box_sep
_box_row "$(printf "  %-20s  %28s" "SonarCloud Issues" "$OPEN_COUNT offen")"
_box_row "$(printf "  %-20s  %28s" "Bericht" "sonar-issues.md")"
_box_bot
echo
