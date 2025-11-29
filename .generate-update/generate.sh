#!/bin/bash
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
cd "$SCRIPT_DIR"
EXCLUDE_ARGS=(-not -path ".")
if [ -f exclude ]; then
  EXCLUDES=($(cat exclude |xargs))
  for line in "${EXCLUDES[@]}"; do
    [ -z "$line" ] || [[ "$line" =~ ^# ]] && continue;
    EXCLUDE_ARGS+=( -not -path "$line" )  
  done
fi
rm -rf update
mkdir -p update
cd ..
find .[^.]* * -maxdepth 0 "${EXCLUDE_ARGS[@]}" > "$SCRIPT_DIR/update/.update_list"
UPDATE_LIST=($(cat "$SCRIPT_DIR/update/.update_list" |xargs))
zip "$SCRIPT_DIR/update/update.zip" "${UPDATE_LIST[@]}" -x "*.DS_Store"
cd "$SCRIPT_DIR/update"
zip -u "$SCRIPT_DIR/update/update.zip" ".update_list"