## Dealing with dataset
- In the root folder of the application, create a "results" folder
- Extract the content of the RAR file in "results" so that the visible path for the included files will be
    -- "results/statements"
    -- "results/statements.json"
- You can now access the result with archer on the URI "mapper.php?folder=statements"

## What datasets are in the files
- Target dataset: MusicBrainz (http://dbtune.org/musicbrainz/sparql/)
- Reference dataset: dbpedia (http://dbpedia.org/sparql)

## File content
- Files named "statements/0_x.json" represent the focus graph of the resource x in the target dataset
- Files named "statements/1_x.json" represent the focus graph of the resource x in the reference dataset
- Each focus graph is an array containing statements in the form (subject, predicates, object, objectMeta(type, datatype))
- The file "statements.json" represent the contextual linkset between the target and the reference datasets