#!/usr/bin/env bash
path="/Users/sijinhe/PhpstormProjects/app-library-backend"
markdownFolder="$path/wiki-markdown"
htmlFolder="$path/wiki-html"
gitList="$path/conf/gitList.txt"
extension=".html"

mkdir -p $markdownFolder
mkdir -p $htmlFolder

cd $markdownFolder && rm -rf *

echo $gitList

while IFS= read line
do
    git clone -b develop "$line"
done <"$gitList"

for dir in `ls ./`;
do
    for file in `ls ./$dir`;
    do
      filename="${file%.*}"
      mkdir -p "$htmlFolder/$dir" && markdown2 --extras fenced-code-blocks "$dir/$file" > "$htmlFolder/$dir/$filename"
      markdown2 --extras fenced-code-blocks "$dir/$file" > "$htmlFolder/$dir/$filename$extension"
    done
done

if wget -SO- -T 1 -t 1 https://appdb-pi.egi.eu/ 2>&1 >/dev/null | grep -c 302; then
    wget https://appdb-pi.egi.eu/rest/1.0/applications?flt=phenomenal -O ../data/appdb.txt
fi
