#!/bin/sh -ex
# https://blog.alexellis.io/mutli-stage-docker-builds/
echo Building squirrel-carddav:build

docker build -t squirrel-carddav:build -f build/Dockerfile.build build

rm -rf x vendor
mkdir x

docker create --name extract squirrel-carddav:build 
docker cp extract:/vendor x
docker rm -f extract

mv x/vendor .
rmdir x

echo Building squirrel-carddav:latest

#docker build --no-cache -t squirrel-carddav:latest .
