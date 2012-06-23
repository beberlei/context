# Terminology

## Model

Layer where business logic and access to persistence happens. Can be sub-divided
into two distinct parts: Interactor and Entity.

## Entity/Data

Data objects which contain application independent business rules.

## Interactor

Use-Case objects that contain application-specific business rules.

## Delivery Mechanism

The delivery mechanism is a way input/output and data processing work in your
application: Human/Machine, Asynchroneous/Synchroneus, HTTP/REST/CLI/GUI, Client/Server, and so on.

## Boundary

Seperates Input from model layer by describing how to map delivery mechanism
inputs into a request against your model. The boundary acts an observer to
model (subject) to listen to success and failure states.

## Context (Interaction)

A context is any class that handles a use-case and therefore very similar to the interactor concept.

## Entity-Boundary-Interactor

Pattern that describes how to seperate delivery mechanisms from model through boundaries.
Interactors contain the behavior of the model, entity represent the static data model.

## Data-Context-Interaction

Pattern that helps solve the mental-model mismatch between static data and behavior.
The Interactor from EBI-Pattern is a context that manages a use-case and the data
objects are "casted into" roles containing behavior. This can be done through aggregation.

# Command Pattern

The context library implements a command-pattern. That means that we build
command object or methods that implement full use-cases. For all use-cases that
manipulate data this means you have to wrap them in methods.

In cases where you only need to display data from the persistence layer and no
logic happens wrapping the code in an additional layer is not necessary.

