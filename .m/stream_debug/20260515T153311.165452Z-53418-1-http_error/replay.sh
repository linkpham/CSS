#!/bin/sh
set -eu
DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
cd /Users/que/Projects/CSS
cargo run --quiet --manifest-path responses_core/Cargo.toml --bin mresp < "$DIR/response.sse"
