# Mapper Code

- [Overview](#overview)
- [Required Attributes](#required-attributes)
- [Data Manipulation](#data-manipulation)
- [Example](#example)
- [Attribute Values](#attribute-values)

<a name="overview"></a>
## Overview

Using a dotted notation format, you define paths to access desired information within the response. 
The data is structured in a multi-dimensional array format and is flattened into a single-level array 
using "dot" notation, indicating depth.

In this context, `data` refers to the document root, serving as the starting point for data extraction.

For example, you can specify that the `id` attribute is located at `data.items.*.id`. This means that 
for each item within the `items` property array in the search response, the system will extract its 
corresponding ID. Similarly, other attributes such as `name`, `image` can be defined in a similar 
manner. Additionally, to access an array item with a specific index `x`, you can use the 
notation `items.x.value`, for example `images.0.url`.

<a name="required-attributes"></a>
## Required Attributes

The following attributes are mandatory for the mapper code:

- `id`: The unique identifier of the document.
- `name`: The name of the document.

While `id` and `name` are mandatory for the mapper code, other attributes are optional.

To define a document image, you can specify the `image` attribute, which should contain the URL of
the image.

<a name="data-manipulation"></a>
## Data Manipulation

Math operations (`-`, `+`, `*`, `/`) and string concatenation (`~`) can also be applied within the
mapper code for further data manipulation.

For example, you can concatenate the `name` and `id` attributes using the following notation:

```yaml
name_id: data.items.*.name ~ data.items.*.id
```

You can also divide the `price` attribute by 100 to convert the price from cents to dollars/euros:

```yaml
price: data.items.*.price / 100
```

Or you can calculate the total price of an item by multiplying the `price` and `quantity` attributes:

```yaml
total_price: data.items.*.price * data.items.*.quantity
```

<a name="example"></a>
## Example

Given the following endpoint response:

```json
{
  "items": [
    {
      "id": "077d9091-050b-4ee9-9c98-576f9c8489fe",
      "name": "Dyson V10 Absolute",
      "price": 58830,
      "quantity": 1,
      "images": [
        {
          "size": "200x200",
          "url": "https://example.org/images/200x200/ef725141-9a29-40a4-a265-b94759e2d8fc.webp"
        },
        {
          "size": "400x400",
          "url": "https://example.org/images/400x400/ef725141-9a29-40a4-a265-b94759e2d8fc.webp"
        }
      ]
    },
    {
      "id": "9fc0522d-84c0-4a2f-ba20-a5f69ed43ab2",
      "name": "Dyson V8 Origin",
      "price": 45093,
      "quantity": 1,
      "images": [
        {
          "size": "200x200",
          "url": "https://example.org/images/200x200/a120fc6b-3aeb-4392-a518-6bae1c8904ed.webp"
        },
        {
          "size": "400x400",
          "url": "https://example.org/images/400x400/a120fc6b-3aeb-4392-a518-6bae1c8904ed.webp"
        }
      ]
    }
  ]
}
```

The mapper code can be defined as follows:

```yaml
id: data.items.*.id
name: data.items.*.name
price: data.items.*.price / 100
quantity: data.items.*.quantity
total_price: (data.items.*.price / 100) * data.items.*.quantity
url: "https://example.org/items/" ~ data.items.*.id
image: data.items.*.images.0.url
```

The resulting output will be an array of objects with the following structure, which will be stored as search snapshots:

```json
[
  {
    "id": "077d9091-050b-4ee9-9c98-576f9c8489fe",
    "name": "Dyson V10 Absolute",
    "price": 588.3,
    "quantity": 1,
    "total_price": 588.3,
    "url": "https://example.org/items/077d9091-050b-4ee9-9c98-576f9c8489fe",
    "image": "https://example.org/images/200x200/ef725141-9a29-40a4-a265-b94759e2d8fc.webp"
  },
  {
    "id": "9fc0522d-84c0-4a2f-ba20-a5f69ed43ab2",
    "name": "Dyson V8 Origin",
    "price": 450.93,
    "quantity": 1,
    "total_price": 450.93,
    "url": "https://example.org/items/9fc0522d-84c0-4a2f-ba20-a5f69ed43ab2",
    "image": "https://example.org/images/200x200/a120fc6b-3aeb-4392-a518-6bae1c8904ed.webp"
  }
]
```

Which will be rendered as follows:

<img src="/images/docs/snapshots.png" alt="Search Snapshots" style="width: 100%; max-width: 762px; height: auto;">

<a name="attribute-values"></a>
## Attribute Values

For any non-mandatory attribute, attribute value can be either a scalar value or an array of scalar.

For example, given the following mapper code (Elasticsearch API response):

```yaml
id: data.hits.hits.*._id
name: data.hits.hits.*._source.name.en
image: data.hits.hits.*._source.image
brand: data.hits.hits.*._source.brand
category: data.hits.hits.*._source.categories.names.en.0
categories: data.hits.hits.*._source.categories.names.en
```

and given the following Elasticsearch API response:

```json
{
  "hits": {
    "hits": [
      {
        "_id": "02edf150-8bcf-4834-b43f-331862aab42f",
        "_source": {
          "name": {
            "en": "KITCHENAID Akku Handrührberät 5KHMB732EBM, Edelstahl/ Kunststoff, Kabellos, 16 W, 7 Geschwindigkeitsstufen, Akku-Anzeige, schwarz"
          },
          "brand": "KitchenAid",
          "image": "https://example.org/images/200x200/02edf150-8bcf-4834-b43f-331862aab42f.webp",
          "categories": {
            "names": {
              "en": [
                "Choppers & cutters",
                "Domestic Kitchen appliances",
                "Kitchen appliances",
                "Kitchen equipment"
              ]
            }
          }
        }
      },
      ...
    ]
  }
}
```

The resulting output will be rendered as follows:

<img src="/images/docs/snapshot2.png" alt="Search Snapshot" style="width: 100%; max-width: 512px; height: auto;">
