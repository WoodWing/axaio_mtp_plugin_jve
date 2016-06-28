/**
 * BasicMetaData.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class BasicMetaData  implements java.io.Serializable {
    private java.lang.String ID;

    private java.lang.String documentID;

    private java.lang.String name;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectType type;

    private com.woodwing.enterprise.interfaces.services.wfl.Publication publication;

    private com.woodwing.enterprise.interfaces.services.wfl.Category category;

    private java.lang.String contentSource;

    public BasicMetaData() {
    }

    public BasicMetaData(
           java.lang.String ID,
           java.lang.String documentID,
           java.lang.String name,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectType type,
           com.woodwing.enterprise.interfaces.services.wfl.Publication publication,
           com.woodwing.enterprise.interfaces.services.wfl.Category category,
           java.lang.String contentSource) {
           this.ID = ID;
           this.documentID = documentID;
           this.name = name;
           this.type = type;
           this.publication = publication;
           this.category = category;
           this.contentSource = contentSource;
    }


    /**
     * Gets the ID value for this BasicMetaData.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this BasicMetaData.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the documentID value for this BasicMetaData.
     * 
     * @return documentID
     */
    public java.lang.String getDocumentID() {
        return documentID;
    }


    /**
     * Sets the documentID value for this BasicMetaData.
     * 
     * @param documentID
     */
    public void setDocumentID(java.lang.String documentID) {
        this.documentID = documentID;
    }


    /**
     * Gets the name value for this BasicMetaData.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this BasicMetaData.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the type value for this BasicMetaData.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectType getType() {
        return type;
    }


    /**
     * Sets the type value for this BasicMetaData.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.wfl.ObjectType type) {
        this.type = type;
    }


    /**
     * Gets the publication value for this BasicMetaData.
     * 
     * @return publication
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Publication getPublication() {
        return publication;
    }


    /**
     * Sets the publication value for this BasicMetaData.
     * 
     * @param publication
     */
    public void setPublication(com.woodwing.enterprise.interfaces.services.wfl.Publication publication) {
        this.publication = publication;
    }


    /**
     * Gets the category value for this BasicMetaData.
     * 
     * @return category
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Category getCategory() {
        return category;
    }


    /**
     * Sets the category value for this BasicMetaData.
     * 
     * @param category
     */
    public void setCategory(com.woodwing.enterprise.interfaces.services.wfl.Category category) {
        this.category = category;
    }


    /**
     * Gets the contentSource value for this BasicMetaData.
     * 
     * @return contentSource
     */
    public java.lang.String getContentSource() {
        return contentSource;
    }


    /**
     * Sets the contentSource value for this BasicMetaData.
     * 
     * @param contentSource
     */
    public void setContentSource(java.lang.String contentSource) {
        this.contentSource = contentSource;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof BasicMetaData)) return false;
        BasicMetaData other = (BasicMetaData) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.documentID==null && other.getDocumentID()==null) || 
             (this.documentID!=null &&
              this.documentID.equals(other.getDocumentID()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.publication==null && other.getPublication()==null) || 
             (this.publication!=null &&
              this.publication.equals(other.getPublication()))) &&
            ((this.category==null && other.getCategory()==null) || 
             (this.category!=null &&
              this.category.equals(other.getCategory()))) &&
            ((this.contentSource==null && other.getContentSource()==null) || 
             (this.contentSource!=null &&
              this.contentSource.equals(other.getContentSource())));
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
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getDocumentID() != null) {
            _hashCode += getDocumentID().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getPublication() != null) {
            _hashCode += getPublication().hashCode();
        }
        if (getCategory() != null) {
            _hashCode += getCategory().hashCode();
        }
        if (getContentSource() != null) {
            _hashCode += getContentSource().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(BasicMetaData.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "BasicMetaData"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("documentID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DocumentID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publication");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publication"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Publication"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("category");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Category"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Category"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("contentSource");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ContentSource"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
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
