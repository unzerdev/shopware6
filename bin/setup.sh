#!/bin/bash

CUR_PATH=$(pwd)

ln -sf "${CUR_PATH}/bin/pre-commit.sh" .git/hooks/pre-commit
