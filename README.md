# Medium Api


## Fetching from Medium
The method that best fits the JSON schema is the RSS feed. It is XML with CDATA HTML tags inside. We can use SimpleXML parser and some "well tested" traversing to grab images and paragraph text that we need.

If we can compose an fixed identifier from the RSS feed we can use this to identify a delta each time we scrape the RSS from our database. This will allow us to have idempotent updates that we can run as often as we need.

To reduce possible points of failure we should not be calling medium at run time, but instead some sort of scheduled task (cron most likely).

##### Flow:
- Update command triggered
- GET request to medium API
- Compose list of unique ids from response
- Run against database to get delta
- Parse required documents into DTOs
- Persist to database
- Flush cache (?)
- Self request to warm cache (?)


## Persisting in Silex

##### ORM Options:
- Doctrine
- Propel


### Doctrine

#### Pros
- Flexible query language
- Strongly typed data structures
- Built in validation

#### Cons
- Large bootstrap
- Time consuming to get performant
- Performance comes best when dealing with complex apps (utilitsing tech like lazy loading + proxies)

### Propel

#### Pros
- Smaller footprint than Doctrine
- Faster for smaller data structures
- Object can be hydrated from XML

#### Cons
- v2.0 is less mature than Doctrine
- Uses active record
- Smaller querying API.


### Digirati recomendation: Propel
With its simple and declerative API propel seems like the best choice. We can type our DTOs and have very simple interactions with a database. Since there are no relationships, Doctrine offers too much overhead for the scale of the medium API.

With propel we can abstract the database so it can be accessed easily with multiple database configurations out of the box.


## Image API

- Example 1: `https://d262ilb51hltx0.cloudfront.net/fit/c/250/150/1*dBy50E2ZsSuteG2LusOQoA.jpeg`
- Example 2: `https://d262ilb51hltx0.cloudfront.net/max/1000/1*dBy50E2ZsSuteG2LusOQoA.jpeg`


##### Parts:
- Example 1: `https://{prefix}.cloudfront.net/{fit/c}/{width}/{height}/{path}.{ext}`
- Example 2: `https://{prefix}.cloudfront.net/{max}/{width}/{path}.{ext}`


##### Proposal:
Configure image variants in config, save images to when scraping API. Configuration example:

```json
{
    "imageVariants": [
        {
            "name": "thumbnail",
            "type": "fit",
            "width": 80,
            "height": 80,
        },
        {
            "name": "splash",
            "type": "max",
            "width": 800
        }
    ]
}
```

These examples will be available at endpoints along the lines of:

 - `/api/medium/images/{id}/thumbnail.jpeg`
 - `/api/medium/images/{id}/splash.jpeg`


## Caching

If we are using a schedule for updating the RSS feed it makes sense to use Expires header that matches the next "update" cycle so that a fresh copy is requested soon after the data is updated. We could also use etag to cover when the data has not changed after an update. This should push all the heavy lifting to Varnish with a minimal amount of requests reaching the service.


## Testing
Unit tests for any functional components such as the XML parsing and image processing is vital.
Controller tests with data fixtures will provide better coverage for the functionality.
[needs name] variation test — swapping out medium.com/@elife for large list of other medium endpoints and testing large volume of variations. (for development to discover bugs, not CI)



