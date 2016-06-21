/**
 * Object.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Object  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData;

    private com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations;

    private com.woodwing.enterprise.interfaces.services.wfl.Page[] pages;

    private com.woodwing.enterprise.interfaces.services.wfl.Attachment[] files;

    private com.woodwing.enterprise.interfaces.services.wfl.Message[] messages;

    private com.woodwing.enterprise.interfaces.services.wfl.Element[] elements;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    private com.woodwing.enterprise.interfaces.services.wfl.EditionRenditionsInfo[] renditions;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels;

    private com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] inDesignArticles;

    private com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] operations;

    public Object() {
    }

    public Object(
           com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData,
           com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations,
           com.woodwing.enterprise.interfaces.services.wfl.Page[] pages,
           com.woodwing.enterprise.interfaces.services.wfl.Attachment[] files,
           com.woodwing.enterprise.interfaces.services.wfl.Message[] messages,
           com.woodwing.enterprise.interfaces.services.wfl.Element[] elements,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets,
           com.woodwing.enterprise.interfaces.services.wfl.EditionRenditionsInfo[] renditions,
           com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels,
           com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] inDesignArticles,
           com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] operations) {
           this.metaData = metaData;
           this.relations = relations;
           this.pages = pages;
           this.files = files;
           this.messages = messages;
           this.elements = elements;
           this.targets = targets;
           this.renditions = renditions;
           this.messageList = messageList;
           this.objectLabels = objectLabels;
           this.inDesignArticles = inDesignArticles;
           this.placements = placements;
           this.operations = operations;
    }


    /**
     * Gets the metaData value for this Object.
     * 
     * @return metaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MetaData getMetaData() {
        return metaData;
    }


    /**
     * Sets the metaData value for this Object.
     * 
     * @param metaData
     */
    public void setMetaData(com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData) {
        this.metaData = metaData;
    }


    /**
     * Gets the relations value for this Object.
     * 
     * @return relations
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Relation[] getRelations() {
        return relations;
    }


    /**
     * Sets the relations value for this Object.
     * 
     * @param relations
     */
    public void setRelations(com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
        this.relations = relations;
    }


    /**
     * Gets the pages value for this Object.
     * 
     * @return pages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Page[] getPages() {
        return pages;
    }


    /**
     * Sets the pages value for this Object.
     * 
     * @param pages
     */
    public void setPages(com.woodwing.enterprise.interfaces.services.wfl.Page[] pages) {
        this.pages = pages;
    }


    /**
     * Gets the files value for this Object.
     * 
     * @return files
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Attachment[] getFiles() {
        return files;
    }


    /**
     * Sets the files value for this Object.
     * 
     * @param files
     */
    public void setFiles(com.woodwing.enterprise.interfaces.services.wfl.Attachment[] files) {
        this.files = files;
    }


    /**
     * Gets the messages value for this Object.
     * 
     * @return messages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Message[] getMessages() {
        return messages;
    }


    /**
     * Sets the messages value for this Object.
     * 
     * @param messages
     */
    public void setMessages(com.woodwing.enterprise.interfaces.services.wfl.Message[] messages) {
        this.messages = messages;
    }


    /**
     * Gets the elements value for this Object.
     * 
     * @return elements
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Element[] getElements() {
        return elements;
    }


    /**
     * Sets the elements value for this Object.
     * 
     * @param elements
     */
    public void setElements(com.woodwing.enterprise.interfaces.services.wfl.Element[] elements) {
        this.elements = elements;
    }


    /**
     * Gets the targets value for this Object.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this Object.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }


    /**
     * Gets the renditions value for this Object.
     * 
     * @return renditions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.EditionRenditionsInfo[] getRenditions() {
        return renditions;
    }


    /**
     * Sets the renditions value for this Object.
     * 
     * @param renditions
     */
    public void setRenditions(com.woodwing.enterprise.interfaces.services.wfl.EditionRenditionsInfo[] renditions) {
        this.renditions = renditions;
    }


    /**
     * Gets the messageList value for this Object.
     * 
     * @return messageList
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageList getMessageList() {
        return messageList;
    }


    /**
     * Sets the messageList value for this Object.
     * 
     * @param messageList
     */
    public void setMessageList(com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList) {
        this.messageList = messageList;
    }


    /**
     * Gets the objectLabels value for this Object.
     * 
     * @return objectLabels
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] getObjectLabels() {
        return objectLabels;
    }


    /**
     * Sets the objectLabels value for this Object.
     * 
     * @param objectLabels
     */
    public void setObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels) {
        this.objectLabels = objectLabels;
    }


    /**
     * Gets the inDesignArticles value for this Object.
     * 
     * @return inDesignArticles
     */
    public com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] getInDesignArticles() {
        return inDesignArticles;
    }


    /**
     * Sets the inDesignArticles value for this Object.
     * 
     * @param inDesignArticles
     */
    public void setInDesignArticles(com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] inDesignArticles) {
        this.inDesignArticles = inDesignArticles;
    }


    /**
     * Gets the placements value for this Object.
     * 
     * @return placements
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Placement[] getPlacements() {
        return placements;
    }


    /**
     * Sets the placements value for this Object.
     * 
     * @param placements
     */
    public void setPlacements(com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements) {
        this.placements = placements;
    }


    /**
     * Gets the operations value for this Object.
     * 
     * @return operations
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] getOperations() {
        return operations;
    }


    /**
     * Sets the operations value for this Object.
     * 
     * @param operations
     */
    public void setOperations(com.woodwing.enterprise.interfaces.services.wfl.ObjectOperation[] operations) {
        this.operations = operations;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Object)) return false;
        Object other = (Object) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.metaData==null && other.getMetaData()==null) || 
             (this.metaData!=null &&
              this.metaData.equals(other.getMetaData()))) &&
            ((this.relations==null && other.getRelations()==null) || 
             (this.relations!=null &&
              java.util.Arrays.equals(this.relations, other.getRelations()))) &&
            ((this.pages==null && other.getPages()==null) || 
             (this.pages!=null &&
              java.util.Arrays.equals(this.pages, other.getPages()))) &&
            ((this.files==null && other.getFiles()==null) || 
             (this.files!=null &&
              java.util.Arrays.equals(this.files, other.getFiles()))) &&
            ((this.messages==null && other.getMessages()==null) || 
             (this.messages!=null &&
              java.util.Arrays.equals(this.messages, other.getMessages()))) &&
            ((this.elements==null && other.getElements()==null) || 
             (this.elements!=null &&
              java.util.Arrays.equals(this.elements, other.getElements()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets()))) &&
            ((this.renditions==null && other.getRenditions()==null) || 
             (this.renditions!=null &&
              java.util.Arrays.equals(this.renditions, other.getRenditions()))) &&
            ((this.messageList==null && other.getMessageList()==null) || 
             (this.messageList!=null &&
              this.messageList.equals(other.getMessageList()))) &&
            ((this.objectLabels==null && other.getObjectLabels()==null) || 
             (this.objectLabels!=null &&
              java.util.Arrays.equals(this.objectLabels, other.getObjectLabels()))) &&
            ((this.inDesignArticles==null && other.getInDesignArticles()==null) || 
             (this.inDesignArticles!=null &&
              java.util.Arrays.equals(this.inDesignArticles, other.getInDesignArticles()))) &&
            ((this.placements==null && other.getPlacements()==null) || 
             (this.placements!=null &&
              java.util.Arrays.equals(this.placements, other.getPlacements()))) &&
            ((this.operations==null && other.getOperations()==null) || 
             (this.operations!=null &&
              java.util.Arrays.equals(this.operations, other.getOperations())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getMetaData() != null) {
            _hashCode += getMetaData().hashCode();
        }
        if (getRelations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRelations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRelations(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFiles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFiles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFiles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getMessages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getMessages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getElements() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getElements());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getElements(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTargets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRenditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRenditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRenditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessageList() != null) {
            _hashCode += getMessageList().hashCode();
        }
        if (getObjectLabels() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjectLabels());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjectLabels(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getInDesignArticles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getInDesignArticles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getInDesignArticles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPlacements() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPlacements());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPlacements(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getOperations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getOperations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getOperations(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Object.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Object"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("metaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaData"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("relations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Relations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Relation"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Relation"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Pages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Page"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Page"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("files");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Files"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Attachment"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Attachment"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Messages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Message"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Message"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("elements");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Elements"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Element"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Element"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Targets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Target"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Target"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("renditions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Renditions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "EditionRenditionsInfo"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "EditionRenditionsInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageList");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageList"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageList"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectLabels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectLabels"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectLabel"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectLabel"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("inDesignArticles");
        elemField.setXmlName(new javax.xml.namespace.QName("", "InDesignArticles"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "InDesignArticle"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "InDesignArticle"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("placements");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Placements"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Placement"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Placement"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("operations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Operations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectOperation"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectOperation"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
