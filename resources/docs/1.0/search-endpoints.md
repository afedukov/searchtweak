# Search Endpoints

- [Concept](#concept)
- [Components of a Search Endpoint](#components-of-a-search-endpoint)
- [Example](#example)
- [Managing Search Endpoints](#managing-search-endpoints)

<a name="concept"></a>
## Concept

A **Search Endpoint** in Search Tweak represents a specific interface to interact with a search service. Each endpoint encapsulates all the necessary details to perform a search request, including the endpoint's name, description, URL, method, mapper code, and http headers.

<a name="components-of-a-search-endpoint"></a>
## Components of a Search Endpoint

- **Name**
  - The name of the search endpoint. This should be a concise, descriptive title that clearly indicates the purpose or functionality of the endpoint.
- **Description** (Optional)
  - A detailed description of what the search endpoint does. This can include the types of queries it handles, the kind of data it retrieves, and any other relevant information.
- **URL** 
  - The URL of the search service. This is the endpoint's address where the search requests are sent.
- **Method** 
  - The HTTP method used to make the request. Common methods include `GET`, `POST`, and `PUT`.
- **Mapper Code** 
  - Mapper code is used to extract specific data attributes from the response. The data is structured in a multi-dimensional array format and is flattened into a single-level array using "dot" notation, indicating depth. For more information, refer to the [Mapper Code](/{{route}}/{{version}}/mapper-code) documentation.
- **Custom Headers** (Optional)
  - Custom headers are additional headers that you would like to send with the request. Each header should be provided on a new line in the format `header: value`.
- **Advanced Settings** (Optional)
    - **Multi-Threading:** Enable multi-threading (`Auto`) to reduce the time taken to fetch data from the endpoint. This feature is useful when dealing with large datasets or slow endpoints.
        If yoo prefer to not overload the endpoint, you can choose `Single` option. This will help to prevent the endpoint from being overwhelmed by too many requests at once. 

<a name="example"></a>
## Example

Here is an example of a search endpoint configuration:

#### Name

Product Search

#### Description

This endpoint handles search queries for products in the e-commerce database. It retrieves product details including name, price, availability, and category.

#### URL

https://api.example.com/search/products

#### Method

GET

#### Mapper Code

```yaml
id: data.items.*.id
name: data.items.*.name
image: data.items.*.image
```

For more details on how to write mapper code, please refer to the [Mapper Code](/{{route}}/{{version}}/mapper-code) documentation.

#### Custom Headers

```yaml
Authorization: Bearer your-api-token
Content-Type: application/json
```


This configuration will send a GET request to `https://api.example.com/search/products` with the specified headers and extract the required attributes from the response using the provided mapper code.

<a name="managing-search-endpoints"></a>
## Managing Search Endpoints

<img src="/images/docs/endpoints.png" alt="Endpoints" style="width: 100%; max-width: 1432px; height: auto;">

The Search Endpoints interface allows you to efficiently manage your search endpoints. The interface provides the following functionalities:

- **List**: View all existing search endpoints.
- **Create Endpoint**: Add a new search endpoint with the required configuration details.
- **Edit**: Modify the details of an existing search endpoint.
- **Activate/Deactivate**: Enable or disable a search endpoint as needed. Deactivating an endpoint will only remove it from the list of available endpoints when creating a new search model.
- **Clone**: Duplicate an existing search endpoint to create a new one with similar settings.
- **Delete**: Remove a search endpoint that is no longer needed.
- **Filter by Status**: Quickly filter endpoints based on their activation status (active/inactive).

These features ensure that you have full control over your search endpoints, making it easy to maintain and optimize your search service configurations.

For more details and to manage your endpoints, visit the [Search Endpoints](/endpoints) in the application.

---

Feel free to explore other sections of the documentation to get a better understanding of how to set up and use Search Tweak effectively.
